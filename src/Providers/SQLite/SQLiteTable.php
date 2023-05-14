<?php declare(strict_types=1);

namespace Composite\Sync\Providers\SQLite;

use Composite\Sync\Providers\AbstractSQLColumn;
use Composite\Sync\Providers\AbstractSQLTable;
use Composite\Entity\Schema;

class SQLiteTable extends AbstractSQLTable
{
    /** @var SQLiteEnum[] */
    public readonly array $enums;

    public function __construct(
        string $name,
        array $columns,
        array $primaryKeys,
        array $indexes,
        array $enums,
    )
    {
        $this->enums = $enums;
        parent::__construct($name, $columns, $primaryKeys, $indexes);
    }

    /**
     * @throws \Exception
     */
    public static function fromEntitySchema(Schema $schema): self
    {
        throw new \Exception('Not Implemented');
    }

    public function getCreateSqlString(): string
    {
        throw new \Exception('Not Implemented');
    }

    public function getDropSqlString(): string
    {
        throw new \Exception('Not Implemented');
    }

    public function getAlterSqlString(array $newColumns, array $changedColumns, array $deletedColumns, bool $primaryKeyChanged): string
    {
        throw new \Exception('Not Implemented');
    }
}