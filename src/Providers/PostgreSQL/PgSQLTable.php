<?php declare(strict_types=1);

namespace Composite\Sync\Providers\PostgreSQL;

use Composite\Sync\Providers\AbstractSQLTable;
use Composite\Entity\Schema;

class PgSQLTable extends AbstractSQLTable
{
    /** @var PgSQLEnum[] */
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