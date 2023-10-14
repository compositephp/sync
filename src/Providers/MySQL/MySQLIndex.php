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
        return new MySQLIndex(
            type: $attribute->isUnique ? MySQLIndexType::UNIQUE : MySQLIndexType::INDEX,
            name: $attribute->name ?: self::generateIndexName($tableName, $attribute),
            columns: $attribute->getColumns(),
            isUnique: $attribute->isUnique,
            order: $attribute->getSort(),
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

    private static function generateIndexName(string $tableName, Attributes\Index $attribute): string
    {
        $parts = [
            $tableName,
            $attribute->isUnique ? 'unq' : 'idx',
        ];
        foreach ($attribute->getColumns() as $columnName) {
            $parts[] = strtolower($columnName);
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
        $parts = [];
        $hasDesc = in_array('DESC', $this->order);
        foreach ($this->columns as $columnName) {
            $part = "`$columnName`";
            if ($hasDesc) {
                $sort = $this->order[$columnName] ?? 'ASC';
                $part .= " $sort";
            }
            $parts[] = $part;
        }
        return '(' . implode(',', $parts) . ')';
    }
}