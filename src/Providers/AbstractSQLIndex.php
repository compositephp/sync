<?php declare(strict_types=1);

namespace Composite\Sync\Providers;

use Composite\Sync\Attributes\Index;

abstract class AbstractSQLIndex
{
    /**
     * @param string[] $columns
     * @param array<string, string> $order example ['column1' => 'DESC', 'column2' => 'ASC']
     */
    public function __construct(
        public readonly string $name,
        public readonly array $columns,
        public readonly bool $isUnique,
        public readonly array $order = [],
    ) {}

    abstract public static function fromEntityAttribute(string $tableName, Index $attribute): self;

    abstract public function getCreateTableSqlString(): string;
    abstract public function getStandaloneSqlString(string $tableName): string;
    abstract public function getDropSqlString(string $tableName): string;

    public function getSanitizedName(string $tableName): string
    {
        $name = str_replace($tableName, '', $this->name);
        $name = str_replace('_idx_', '', $name);
        $name = str_replace('_unq_', '', $name);
        $name = preg_replace('/_+/', '_', $name);
        return trim($name, '_');
    }
}