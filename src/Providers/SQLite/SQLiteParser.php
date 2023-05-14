<?php declare(strict_types=1);

namespace Composite\Sync\Providers\SQLite;

use Composite\Sync\Providers\AbstractSQLTable;
use Doctrine\DBAL\Connection;

class SQLiteParser
{
    public const TABLE_SQL = "SELECT `sql` FROM sqlite_schema WHERE name = :tableName";
    public const INDEXES_SQL = "SELECT `sql` FROM sqlite_master WHERE type = 'index' and tbl_name = :tableName";

    private const COLUMN_PATTERN = '/^(?!constraint|primary key)(?:`|\"|\')?(\w+)(?:`|\"|\')? ([a-zA-Z]+)\s?(\(([\d,\s]+)\))?/i';
    private const CONSTRAINT_PATTERN = '/^(?:constraint) (?:`|\"|\')?\w+(?:`|\"|\')? primary key \(([\w\s,\'\"`]+)\)/i';
    private const PRIMARY_KEY_PATTERN = '/^primary key \(([\w\s,\'\"`]+)\)/i';
    private const ENUM_PATTERN = '/check \((?:`|\"|\')?(\w+)(?:`|\"|\')? in \((.+)\)\)/i';

    private readonly string $tableName;
    private readonly string $tableSql;
    private readonly array $indexesSql;

    public function __construct(
        Connection $connection,
        string $tableName,
    ) {
        $this->tableName = $tableName;
        $this->tableSql = $connection->executeQuery(
            sql: self::TABLE_SQL,
            params: ['tableName' => $tableName],
        )->fetchOne();
        $this->indexesSql = $connection->executeQuery(
            sql: self::INDEXES_SQL,
            params: ['tableName' => $tableName],
        )->fetchFirstColumn();
    }

    public function getSQLTable(): AbstractSQLTable
    {
        $columns = $enums = $primaryKeys = [];
        $columnsStarted = false;
        $lines = array_map(
            fn ($line) => trim(preg_replace("/\s+/", " ", $line)),
            explode("\n", $this->tableSql),
        );
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (!$line) {
                continue;
            }
            if (!$columnsStarted) {
                if (\str_starts_with($line, '(') || \str_ends_with($line, '(')) {
                    $columnsStarted = true;
                }
                continue;
            }
            if ($line === ')') {
                break;
            }
            if (!\str_ends_with($line, ',')) {
                if (!empty($lines[$i + 1]) && !\str_starts_with($lines[$i + 1], ')')) {
                    $lines[$i + 1] = $line . ' ' . $lines[$i + 1];
                    continue;
                }
            }
            if ($column = $this->parseSQLColumn($line)) {
                $columns[$column->name] = $column;
            }
            if ($enum = $this->parseEnum($line)) {
                $enums[$column?->name ?? $enum->name] = $enum;
            }
            $primaryKeys = array_merge($primaryKeys, $this->parsePrimaryKeys($line));
        }
        return new SQLiteTable(
            name: $this->tableName,
            columns: $columns,
            primaryKeys: array_unique($primaryKeys),
            indexes: $this->getIndexes(),
            enums: $enums,
        );
    }

    private function parseSQLColumn(string $sqlLine): ?SQLiteColumn
    {
        if (!preg_match(self::COLUMN_PATTERN, $sqlLine, $matches)) {
            return null;
        }
        $name = $matches[1];
        $rawType = $matches[2];
        $rawTypeParams = !empty($matches[4]) ? str_replace(' ', '', $matches[4]) : null;
        $type = $this->getColumnType($rawType);
        $hasDefaultValue = stripos($sqlLine, ' default ') !== false;
        $enum = $this->parseEnum($sqlLine);
        return new SQLiteColumn(
            name: $name,
            type: $type,
            size: $this->getColumnSize($type, $rawTypeParams),
            precision: $this->getColumnPrecision($type, $rawTypeParams),
            scale: $this->getScale($type, $rawTypeParams),
            isNullable: stripos($sqlLine, ' not null') === false,
            hasDefaultValue: $hasDefaultValue,
            defaultValue: $hasDefaultValue ? $this->getDefaultValue($sqlLine) : null,
            isAutoincrement: stripos($sqlLine, ' autoincrement') !== false,
            values: $enum?->values ?? null,
        );
    }

    private function getColumnType(string $rawType): SQLiteColumnType
    {
        preg_match('/^([a-zA-Z]+).*/', $rawType, $matches);
        $type = strtoupper($matches[1] ?? null);
        return SQLiteColumnType::from($type);
    }

    private function getColumnSize(SQLiteColumnType $type, ?string $typeParams): ?int
    {
        if ($type->isString() || !$typeParams) {
            return null;
        }
        return (int)$typeParams;
    }

    private function getColumnPrecision(SQLiteColumnType $type, ?string $typeParams): ?int
    {
        if (!$type->isFloat() || !$typeParams) {
            return null;
        }
        $parts = explode(',', $typeParams);
        return (int)$parts[0];
    }

    private function getScale(SQLiteColumnType $type, ?string $typeParams): ?int
    {
        if (!$type->isFloat() || !$typeParams) {
            return null;
        }
        $parts = explode(',', $typeParams);
        return !empty($parts[1]) ? (int)$parts[1] : null;
    }

    private function getDefaultValue(string $sqlLine): mixed
    {
        $sqlLine = $this->cleanCheckEnum($sqlLine);
        if (preg_match('/default\s+\'(.*)\'/iu', $sqlLine, $matches)) {
            return $matches[1];
        } elseif (preg_match('/default\s+([\w.]+)/iu', $sqlLine, $matches)) {
            $defaultValue = $matches[1];
            if (strtolower($defaultValue) === 'null') {
                return null;
            }
            return $defaultValue;
        }
        return null;
    }

    private function parsePrimaryKeys(string $sqlLine): array
    {
        if (preg_match(self::COLUMN_PATTERN, $sqlLine, $matches)) {
            $name = $matches[1];
            return stripos($sqlLine, ' primary key') !== false ? [$name] : [];
        }
        if (!preg_match(self::CONSTRAINT_PATTERN, $sqlLine, $matches)
            && !preg_match(self::PRIMARY_KEY_PATTERN, $sqlLine, $matches)) {
            return [];
        }
        $primaryColumnsRaw = $matches[1];
        $primaryColumnsRaw = str_replace(['\'', '"', '`', ' '], '', $primaryColumnsRaw);
        return explode(',', $primaryColumnsRaw);
    }

    private function parseEnum(string $sqlLine): ?SQLiteEnum
    {
        if (!preg_match(self::ENUM_PATTERN, $sqlLine, $matches)) {
            return null;
        }
        $name = $matches[1];
        $values = [];
        $sqlValues = array_map('trim', explode(',', $matches[2]));
        foreach ($sqlValues as $value) {
            $value = trim($value);
            if (\str_starts_with($value, '\'')) {
                $value = trim($value, '\'');
            } elseif (\str_starts_with($value, '"')) {
                $value = trim($value, '"');
            }
            $values[] = $value;
        }
        return new SQLiteEnum(name: $name, values: $values);
    }

    /**
     * @return SQLiteIndex[]
     */
    private function getIndexes(): array
    {
        $result = [];
        foreach ($this->indexesSql as $indexSql) {
            if (!$indexSql) continue;

            $indexSql = trim(str_replace("\n", " ", $indexSql));
            $indexSql = preg_replace("/\s+/", " ", $indexSql);
            if (!preg_match('/index\s+(?:`|\"|\')?(\w+)(?:`|\"|\')?/i', $indexSql, $nameMatch)) {
                continue;
            }
            $name = $nameMatch[1];
            if (!preg_match('/\(([`"\',\s\w]+)\)/', $indexSql, $columnsMatch)) {
                continue;
            }
            $columnsRaw = array_map(
                fn (string $column) => str_replace(['`', '\'', '"'], '', trim($column)),
                explode(',', $columnsMatch[1])
            );
            $columns = $sort = [];
            foreach ($columnsRaw as $columnRaw) {
                $parts = explode(' ', $columnRaw);
                $columns[] = $parts[0];
                if (!empty($parts[1])) {
                    $sort[$parts[0]] = strtolower($parts[1]);
                }
            }
            $result[] = new SQLiteIndex(
                type: SQLiteIndexType::PRIMARY,
                name: $name,
                columns: $columns,
                isUnique: stripos($indexSql, ' unique index ') !== false,
                order: $sort,
            );
        }
        return $result;
    }

    private function cleanCheckEnum(string $sqlLine): string
    {
        return preg_replace('/ check \(\"\w+\" IN \(.+\)\)/i', '', $sqlLine);
    }
}