<?php declare(strict_types=1);

namespace Composite\Sync\Providers;

abstract class AbstractComparator
{
    /** @var string[]  */
    public readonly array $newColumns;
    /** @var string[]  */
    public readonly array $changedColumns;
    /** @var AbstractSQLIndex[] */
    public readonly array $newIndexes;
    /** @var AbstractSQLIndex[] */
    public readonly array $deletedIndexes;
    public readonly bool $primaryKeyChanged;

    public function __construct(
        public readonly string $connectionName,
        protected readonly AbstractSQLTable $entityTable,
        protected readonly ?AbstractSQLTable $databaseTable,
    ) {
        $newColumns = $changedColumns = [];
        foreach ($this->entityTable->columns as $entityColumn) {
            $dbColumn = $this->databaseTable?->getColumnByName($entityColumn->name);
            if (!$dbColumn) {
                $newColumns[] = $entityColumn->name;
            } elseif (!$this->columnsAreEqual($entityColumn, $dbColumn)) {
                $changedColumns[] = $entityColumn->name;
            }
        }
        $this->newColumns = $newColumns;
        $this->changedColumns = $changedColumns;

        $newIndexes = $deletedIndexes = [];
        foreach ($this->entityTable->indexes as $entityIndex) {
            $dbIndex = $this->databaseTable?->getIndexByName($entityIndex->name);
            if (!$dbIndex) {
                $newIndexes[] = $entityIndex;
            } elseif (!$this->indexesAreEqual($entityIndex, $dbIndex)) {
                $newIndexes[] = $entityIndex;
                $deletedIndexes[] = $dbIndex;
            }
        }
        foreach ($this->databaseTable?->indexes ?? [] as $dbIndex) {
            if (!$this->entityTable->getIndexByName($dbIndex->name)) {
                $deletedIndexes[] = $dbIndex;
            }
        }
        $this->newIndexes = $newIndexes;
        $this->deletedIndexes = $deletedIndexes;
        $this->primaryKeyChanged = $this->databaseTable && $this->entityTable->primaryKeys !== $this->databaseTable->primaryKeys;
    }

    abstract public function getUpQueries(): array;
    abstract public function getDownQueries(): array;

    public function getSummaryParts(): array
    {
        $parts = [];
        if ($this->databaseTable) {
            $parts[] = 'alter';
            $parts[] = $this->entityTable->name;
            if ($this->newColumns) {
                $parts[] = '_add';
                $parts = array_merge($parts, $this->newColumns);
            }
            if ($this->changedColumns) {
                $parts[] = '_chg';
                $parts = array_merge($parts, $this->changedColumns);
            }
            if ($this->primaryKeyChanged) {
                if ($this->entityTable->primaryKeys) {
                    $parts[] = '_chg_pk';
                    $parts = array_merge($parts, $this->entityTable->primaryKeys);
                } else {
                    $parts[] = '_drp_pk';
                }
            }

            /** @var AbstractSQLIndex[] $addedIndexes */
            $addedIndexes   = array_filter($this->newIndexes, fn ($index) => empty($this->deletedIndexes[$index->name]));
            /** @var AbstractSQLIndex[] $changedIndexes */
            $changedIndexes = array_filter($this->newIndexes, fn ($index) => !empty($this->deletedIndexes[$index->name]));
            /** @var AbstractSQLIndex[] $deletedIndexes */
            $deletedIndexes = array_filter($this->deletedIndexes, fn ($index) => empty($this->newIndexes[$index->name]));

            if ($addedIndexes) {
                $parts[] = '_add_idx';
                $parts = array_merge(
                    $parts,
                    array_map(fn ($index) => $index->getSanitizedName($this->entityTable->name), $addedIndexes),
                );
            }
            if ($changedIndexes) {
                $parts[] = '_chg_idx';
                $parts = array_merge(
                    $parts,
                    array_map(fn ($index) => $index->getSanitizedName($this->entityTable->name), $changedIndexes),
                );
            }
            if ($deletedIndexes) {
                $parts[] = '_drp_idx';
                $parts = array_merge(
                    $parts,
                    array_map(fn ($index) => $index->getSanitizedName($this->entityTable->name), $deletedIndexes),
                );
            }
        } else {
            $parts[] = 'create';
            $parts[] = $this->entityTable->name;
        }
        return $parts;
    }

    protected function columnsAreEqual(AbstractSQLColumn $entityColumn, AbstractSQLColumn $dbColumn): bool
    {
        return $entityColumn->getSqlString() === $dbColumn->getSqlString();
    }

    protected function indexesAreEqual(AbstractSQLIndex $entityIndex, AbstractSQLIndex $dbIndex): bool
    {
        return $entityIndex->getCreateTableSqlString() === $dbIndex->getCreateTableSqlString();
    }
}