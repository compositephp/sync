<?php declare(strict_types=1);

namespace Composite\Sync\Attributes;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS)]
class Index
{
    public function __construct(
        private readonly array $columns,
        public readonly bool $isUnique = false,
        public readonly ?string $name = null,
    ) {}

    public function getColumns(): array
    {
        return array_keys($this->getColumnsWithSort());
    }

    /**
     * @return array<string, string>
     */
    public function getSort(): array
    {
        $sort = $this->getColumnsWithSort();
        return in_array('DESC', $sort) ? $sort : [];
    }

    /**
     * @return array<string, string>
     */
    private function getColumnsWithSort(): array
    {
        $result = [];
        foreach ($this->columns as $columnName => $order) {
            if (is_numeric($columnName)) {
                $indexColumn = $order;
                $indexOrder = 'ASC';
            } else {
                $indexColumn = $columnName;
                $indexOrder = strtoupper($order);
            }
            if ($indexOrder !== 'ASC' && $indexOrder !== 'DESC') {
                throw new \Exception(sprintf("Unknown order `%s` in index column `%s`", $indexOrder, $indexColumn));
            }
            $result[$indexColumn] = $indexOrder;
        }
        return $result;
    }
}