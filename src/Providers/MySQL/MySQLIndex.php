<?php declare(strict_types=1);

namespace Composite\Sync\Providers\MySQL;

use Composite\Sync\Helpers\Template;
use Composite\Sync\Providers\AbstractSQLIndex;
use Composite\Sync\Attributes;

class MySQLIndex extends AbstractSQLIndex
{
    public readonly MySQLIndexType $type;

    public function __construct(
        MySQLIndexType $type,
        string $name,
        array $columns,
        bool $isUnique,
        array $order = [],
    ) {
        $this->type = $type;
        parent::__construct(
            name: $name,
            columns: $columns,
            isUnique: $isUnique,
            order: $order,
        );
    }

    /**
     * @throws \Exception
     */
    public static function fromEntityAttribute(string $tableName, Attributes\Index $attribute): self
    {
        $columns = array_is_list($attribute->columns) ? $attribute->columns : array_keys($attribute->columns);
        $order = self::getOrder($attribute);
        return new MySQLIndex(
            type: $attribute->isUnique ? MySQLIndexType::UNIQUE : MySQLIndexType::INDEX,
            name: $attribute->name ?: self::generateIndexName($tableName, $attribute->isUnique, $columns, $order),
            columns: $columns,
            isUnique: $attribute->isUnique,
            order: $order,
        );
    }

    /**
     * @throws \Exception
     */
    public static function fromSqlParserArray(array $data): MySQLIndex
    {
        if (!$typeRaw = $data['type'] ?? null) {
            throw new \Exception(sprintf('Missed field `type` in index array'));
        }
        $type = MySQLIndexType::fromString($typeRaw);
        $name = $data['name'] ?? '';
        $columns = $order = [];
        foreach ($data['cols'] as $col) {
            $columns[] = $col['name'];
            if (!empty($col['direction'])) {
                $order[$col['name']] = strtoupper($col['direction']);
            }
        }
        $isUnique = $type == MySQLIndexType::UNIQUE || $type == MySQLIndexType::PRIMARY;
        return new MySQLIndex(
            type: $type,
            name: $name,
            columns: $columns,
            isUnique: $isUnique,
            order: $order,
        );
    }

    private static function getOrder(Attributes\Index $index): array
    {
        $columns = $index->columns;
        if (array_is_list($columns)) {
            return [];
        }
        $result = [];
        foreach ($columns as $columnName => $order) {
            $order = strtoupper($order);
            if ($order !== 'ASC' && $order !== 'DESC') {
                throw new \Exception(sprintf("Unknown order `%s` in index column `%s`", $order, $columns[$columnName]));
            }
            $result[$columnName] = $order;
        }
        return $result;
    }

    private static function generateIndexName(string $tableName, bool $isUnique, array $columns, array $order): string
    {
        $parts = [
            $tableName,
            $isUnique ? 'unq' : 'idx',
        ];
        if ($order) {
            foreach ($order as $columnName => $orderDir) {
                $parts[] = strtolower($columnName);
                $parts[] = strtolower($orderDir);
            }
        } else {
            $parts = array_merge($parts, array_map('strtolower', $columns));
        }
        return implode('_', $parts);
    }

    public function getCreateTableSqlString(): string
    {
        $parts = [];
        if ($this->type === MySQLIndexType::INDEX) {
            $parts[] = 'KEY';
        } elseif ($this->type === MySQLIndexType::PRIMARY) {
            $parts[] = 'PRIMARY KEY';
        } elseif ($this->type === MySQLIndexType::UNIQUE) {
            $parts[] = 'UNIQUE KEY';
        } elseif ($this->type === MySQLIndexType::FULLTEXT) {
            $parts[] = 'FULLTEXT KEY';
        } else {
            throw new \Exception(sprintf('Index type %s is not supported', $this->type->name));
        }
        if ($this->name) {
            $parts[] = "`$this->name`";
        }
        $parts[] = $this->getSqlColumnsString();
        return implode(' ', $parts);
    }

    /**
     * @throws \Exception
     */
    public function getStandaloneSqlString(string $tableName): string
    {
        $parts = [];
        if ($this->type === MySQLIndexType::INDEX) {
            $parts[] = 'CREATE INDEX';
        } elseif ($this->type === MySQLIndexType::PRIMARY) {
            $parts[] = 'CREATE PRIMARY KEY';
        } elseif ($this->type === MySQLIndexType::UNIQUE) {
            $parts[] = 'CREATE UNIQUE KEY';
        } elseif ($this->type === MySQLIndexType::FULLTEXT) {
            $parts[] = 'CREATE FULLTEXT KEY';
        } else {
            throw new \Exception(sprintf('Index type %s is not supported', $this->type->name));
        }
        if ($this->name && $this->type !== MySQLIndexType::PRIMARY) {
            $parts[] = "`$this->name`";
        }
        $parts[] = "ON `$tableName`";
        $parts[] = $this->getSqlColumnsString();
        return "    " . implode(' ', $parts) . ';';
    }

    public function getDropSqlString(string $tableName): string
    {
        $templateVars = [
            'tableName' => $tableName,
            'indexName' => $this->name,
        ];
        return Template::render('templates/drop_index_sql', $templateVars);
    }

    private function getSqlColumnsString(): string
    {
        if ($this->order) {
            $columns = [];
            foreach ($this->order as $columnName => $order) {
                $columns[] = "`$columnName` $order";
            }
        } else {
            $columns = array_map(fn ($column) => "`$column`", $this->columns);
        }
        return '(' . implode(',', $columns) . ')';
    }
}