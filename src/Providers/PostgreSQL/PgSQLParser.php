<?php declare(strict_types=1);

namespace Composite\Sync\Providers\PostgreSQL;

use Composite\Sync\Providers\AbstractSQLTable;
use Doctrine\DBAL\Connection;

class PgSQLParser
{
    public const COLUMNS_SQL = "
        SELECT * FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = :tableName;
     ";

    public const INDEXES_SQL = "
        SELECT * FROM pg_indexes
        WHERE schemaname = 'public' AND tablename = :tableName;
     ";

    public const PRIMARY_KEY_SQL = <<<SQL
        SELECT a.attname as column_name
        FROM pg_index i
        JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY (i.indkey)
        WHERE i.indrelid = '":tableName"'::regclass AND i.indisprimary;
     SQL;

    public const ALL_ENUMS_SQL = "
        SELECT t.typname as enum_name, e.enumlabel as enum_value
        FROM pg_type t
        JOIN pg_enum e ON t.oid = e.enumtypid
        JOIN pg_catalog.pg_namespace n ON n.oid = t.typnamespace
        WHERE n.nspname = 'public';
    ";

    private readonly string $tableName;
    private readonly array $informationSchemaColumns;
    private readonly array $informationSchemaIndexes;
    private readonly array $primaryKeys;
    private readonly array $allEnums;

    public static function getPrimaryKeySQL(string $tableName): string
    {
        return "
            SELECT a.attname as column_name
            FROM pg_index i
            JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY (i.indkey)
            WHERE i.indrelid = '\"" . $tableName . "\"'::regclass AND i.indisprimary;
        ";
    }

    public function __construct(Connection $connection, string $tableName)
    {
        $this->tableName = $tableName;
        $this->informationSchemaColumns = $connection->executeQuery(
            sql: self::COLUMNS_SQL,
            params: ['tableName' => $tableName],
        )->fetchAllAssociative();
        $this->informationSchemaIndexes = $connection->executeQuery(
            sql: self::INDEXES_SQL,
            params: ['tableName' => $tableName],
        )->fetchAllAssociative();

        if ($primaryKeySQL = self::getPrimaryKeySQL($tableName)) {
            $primaryKeys = array_map(
                fn(array $row): string => $row['column_name'],
                $connection->executeQuery($primaryKeySQL)->fetchAllAssociative()
            );
        } else {
            $primaryKeys = [];
        }
        $this->primaryKeys = $primaryKeys;

        $allEnumsRaw = $connection->executeQuery(self::ALL_ENUMS_SQL)->fetchAllAssociative();
        $allEnums = [];
        foreach ($allEnumsRaw as $enumRaw) {
            $name = $enumRaw['enum_name'];
            $value = $enumRaw['enum_value'];
            if (!isset($allEnums[$name])) {
                $allEnums[$name] = [];
            }
            $allEnums[$name][] = $value;
        }
        $this->allEnums = $allEnums;
    }

    public function getSQLTable(): AbstractSQLTable
    {
        $columns = $enums = [];
        foreach ($this->informationSchemaColumns as $informationSchemaColumn) {
            $name = $informationSchemaColumn['column_name'];
            $type = $this->getType($informationSchemaColumn);
            $sqlDefault = $informationSchemaColumn['column_default'];
            $isNullable = $informationSchemaColumn['is_nullable'] === 'YES';
            $defaultValue = $this->getDefaultValue($sqlDefault);
            $hasDefaultValue = $defaultValue !== null || $isNullable;
            $isAutoincrement = $sqlDefault && \str_starts_with($sqlDefault, 'nextval(');

            if ($type === PgSQLColumnType::ENUM) {
                $udtName = $informationSchemaColumn['udt_name'];
                $enums[$name] = new PgSQLEnum(name: $udtName, values: $this->allEnums[$udtName]);
            }
            $column = new PgSQLColumn(
                name: $name,
                type: $type,
                size: $this->getSize($type, $informationSchemaColumn),
                precision: $this->getPrecision($type, $informationSchemaColumn),
                scale: $this->getScale($type, $informationSchemaColumn),
                isNullable: $isNullable,
                hasDefaultValue: $hasDefaultValue,
                defaultValue: $defaultValue,
                isAutoincrement: $isAutoincrement,
                values: [],
            );
            $columns[$column->name] = $column;
        }
        return new PgSQLTable(
            name: $this->tableName,
            columns: $columns,
            primaryKeys: $this->primaryKeys,
            indexes: $this->parseIndexes(),
            enums: $enums,
        );
    }

    private function getType(array $informationSchemaColumn): PgSQLColumnType
    {
        $dataType = $informationSchemaColumn['data_type'];
        $udtName = $informationSchemaColumn['udt_name'];
        if ($dataType === 'USER-DEFINED' && !empty($this->allEnums[$udtName])) {
            return PgSQLColumnType::ENUM;
        }
        if (preg_match('/^int(\d?)$/', $udtName)) {
            return PgSQLColumnType::INT;
        }
        if (preg_match('/^float(\d?)$/', $udtName)) {
            return PgSQLColumnType::FLOAT;
        }
        return PgSQLColumnType::from($udtName);
    }

    private function getSize(PgSQLColumnType $type, array $informationSchemaColumn): ?int
    {
        if (!$type->isString()) {
            return null;
        }
        return $informationSchemaColumn['character_maximum_length'];
    }

    private function getPrecision(PgSQLColumnType $type, array $informationSchemaColumn): ?int
    {
        if (!$type->isFloat()) {
            return null;
        }
        return $informationSchemaColumn['numeric_precision'] ?? null;
    }

    private function getScale(PgSQLColumnType $type, array $informationSchemaColumn): ?int
    {
        if (!$type->isFloat()) {
            return null;
        }
        return $informationSchemaColumn['numeric_scale'] ?? null;
    }

    private function getDefaultValue(?string $sqlValue): mixed
    {
        if ($sqlValue === null || strcasecmp($sqlValue, 'null') === 0 || str_starts_with($sqlValue, 'NULL::')) {
            return  null;
        }
        if (\str_starts_with($sqlValue, 'nextval(')) {
            return null;
        }
        $parts = explode('::', $sqlValue);
        return trim($parts[0], '\'');
    }

    private function parseIndexes(): array
    {
        $result = [];
        foreach ($this->informationSchemaIndexes as $informationSchemaIndex) {
            $name = $informationSchemaIndex['indexname'];
            $sql = $informationSchemaIndex['indexdef'];
            $isUnique = stripos($sql, ' unique index ') !== false;

            if (!preg_match('/\(([`"\',\s\w]+)\)/', $sql, $columnsMatch)) {
                continue;
            }
            $columnsRaw = array_map(
                fn (string $column) => str_replace(['`', '\'', '"'], '', trim($column)),
                explode(',', $columnsMatch[1])
            );
            $columns = $order = [];
            foreach ($columnsRaw as $columnRaw) {
                $parts = explode(' ', $columnRaw);
                $columns[] = $parts[0];
                if (!empty($parts[1])) {
                    $order[$parts[0]] = strtoupper($parts[1]);
                }
            }
            if ($columns === $this->primaryKeys) {
                continue;
            }
            $result[] = new PgSQLIndex(
                type: $isUnique ? PgSQLIndexType::UNIQUE : PgSQLIndexType::INDEX,
                name: $name,
                columns: $columns,
                isUnique: $isUnique,
                order: $order,
            );
        }
        return $result;
    }
}