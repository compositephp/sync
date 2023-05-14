<?php declare(strict_types=1);

namespace Composite\Sync\Providers;

use Composite\Entity\Schema;

abstract class AbstractSQLTable
{
    /**
     * @param AbstractSQLColumn[] $columns
     * @param AbstractSQLIndex[] $indexes
     */
    public function __construct(
        public readonly string $name,
        public readonly array $columns,
        public readonly array $primaryKeys,
        public readonly array $indexes,
    ) {}

    abstract public static function fromEntitySchema(Schema $schema): self;

    abstract public function getCreateSqlString(): string;
    abstract public function getDropSqlString(): string;
    abstract public function getAlterSqlString(array $newColumns, array $changedColumns, array $deletedColumns, bool $primaryKeyChanged): string;

    public function getColumnByName(string $name): ?AbstractSQLColumn
    {
        foreach ($this->columns as $column) {
            if ($column->name === $name) {
                return $column;
            }
        }
        return null;
    }

    public function getIndexByName(string $name): ?AbstractSQLIndex
    {
        foreach ($this->indexes as $index) {
            if ($index->name === $name) {
                return $index;
            }
        }
        return null;
    }

    /**
     * @return AbstractSQLColumn[]
     */
    public function getEnumColumns(): array
    {
        return array_filter($this->columns, fn ($column) => $column->values || !empty($this->enums[$column->name]));
    }
}