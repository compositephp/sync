<?php declare(strict_types=1);

namespace Composite\Sync\Providers\SQLite;

use Composite\Sync\Providers\AbstractSQLIndex;
use Composite\Sync\Attributes;

class SQLiteIndex extends AbstractSQLIndex
{
    public readonly SQLiteIndexType $type;

    public function __construct(
        SQLiteIndexType $type,
        string $name,
        array $columns,
        bool $isUnique,
        array $order,
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
        throw new \Exception('Not Implemented');
    }

    public function getCreateTableSqlString(): string
    {
        throw new \Exception('Not Implemented');
    }

    /**
     * @throws \Exception
     */
    public function getStandaloneSqlString(string $tableName): string
    {
        throw new \Exception('Not Implemented');
    }

    public function getDropSqlString(string $tableName): string
    {
        throw new \Exception('Not Implemented');
    }
}