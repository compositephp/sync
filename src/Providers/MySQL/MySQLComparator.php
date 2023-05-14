<?php declare(strict_types=1);

namespace Composite\Sync\Providers\MySQL;

use Composite\Sync\Providers\AbstractComparator;
use Composite\Sync\Providers\AbstractSQLColumn;

/**
 * @property MySQLTable $entityTable
 * @property MySQLTable|null $databaseTable
 */
class MySQLComparator extends AbstractComparator
{
    public function getUpQueries(): array
    {
        $result = [];
        if ($this->databaseTable) {
            $result[] = $this->entityTable->getAlterSqlString(
                newColumns: $this->newColumns,
                changedColumns: $this->changedColumns,
                deletedColumns: [],
                primaryKeyChanged: $this->primaryKeyChanged,
            );
            foreach ($this->deletedIndexes as $deletedIndex) {
                $result[] = $deletedIndex->getDropSqlString($this->entityTable->name);
            }
            foreach ($this->newIndexes as $newIndex) {
                $result[] = $newIndex->getStandaloneSqlString($this->entityTable->name);
            }
        } else {
            $result[] = $this->entityTable->getCreateSqlString();
        }
        return array_filter($result);
    }

    public function getDownQueries(): array
    {
        $result = [];
        if ($this->databaseTable) {
            $result[] = $this->databaseTable->getAlterSqlString(
                newColumns: [],
                changedColumns: $this->changedColumns,
                deletedColumns: $this->newColumns,
                primaryKeyChanged: $this->primaryKeyChanged,
            );
            foreach ($this->newIndexes as $newIndex) {
                $result[] = $newIndex->getDropSqlString($this->entityTable->name);
            }
            foreach ($this->deletedIndexes as $deletedIndex) {
                $result[] = $deletedIndex->getStandaloneSqlString($this->entityTable->name);
            }
        } else {
            $result[] = $this->entityTable->getDropSqlString();
        }
        return array_filter($result);
    }

    protected function columnsAreEqual(MySQLColumn|AbstractSQLColumn $entityColumn, MySQLColumn|AbstractSQLColumn $dbColumn): bool
    {
        if ($entityColumn->getSqlString() === $dbColumn->getSqlString()) {
            return true;
        }
        $reflectionClass = new \ReflectionClass(MySQLColumn::class);
        $diffColumns = [];
        foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyName = $property->getName();
            $entityValue = $entityColumn->{$propertyName};
            $dbValue = $dbColumn->{$propertyName};
            if (is_numeric($entityValue)) {
                $entityValue = (string)$entityValue;
            }
            if (is_numeric($dbValue)) {
                $dbValue = (string)$dbValue;
            }
            if ($entityValue !== $dbValue) {
                $diffColumns[$propertyName] = [$entityValue, $dbValue];
            }
        }
        if (!empty($diffColumns['collation'])) {
            $entityCollation = $diffColumns['collation'][0];
            $dbCollation = $diffColumns['collation'][1];
            if (!$entityCollation && $dbCollation === $this->databaseTable->collation) {
                unset($diffColumns['collation']);
            }
        }
        if (!empty($diffColumns['hasDefaultValue'])) {
            if ($entityColumn->defaultValue === $dbColumn->defaultValue && $entityColumn->isNullable === $dbColumn->isNullable) {
                unset($diffColumns['hasDefaultValue']);
            }
        }
        return empty($diffColumns);
    }
}