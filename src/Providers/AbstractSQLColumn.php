<?php declare(strict_types=1);

namespace Composite\Sync\Providers;

use Composite\Entity\Columns\AbstractColumn;
use Composite\Entity\Columns\CastableColumn;
use Composite\Entity\Columns\EntityColumn;
use Composite\Sync\Attributes\Column;

abstract class AbstractSQLColumn
{
    public function __construct(
        public readonly string $name,
        public readonly \UnitEnum $type,
        public readonly ?int $size,
        public readonly ?int $precision,
        public readonly ?int $scale,
        public readonly bool $isNullable,
        public readonly bool $hasDefaultValue,
        public readonly mixed $defaultValue,
        public readonly bool $isAutoincrement,
        public readonly ?array $values,
    ) {}

    abstract public static function fromEntityColumn(AbstractColumn $entityColumn): self;

    abstract public function getSqlString(): string;

    abstract public function getEntityType(): EntityColumnType;

    public function sizeIsDefault(): bool
    {
        if ($this->getEntityType() !== EntityColumnType::String) {
            return true;
        }
        if ($this->size === null) {
            return true;
        }
        return $this->size === 255;
    }

    public function getColumnAttributeProperties(): array
    {
        $result = [];
        if ($this->size && !$this->sizeIsDefault()) {
            $result[] = 'size: ' . $this->size;
        }
        if ($this->precision) {
            $result[] = 'precision: ' . $this->precision;
        }
        if ($this->scale) {
            $result[] = 'scale: ' . $this->scale;
        }
        return $result;
    }

    public static function getColumnAttribute(AbstractColumn $entityColumn): ?Column
    {
        if ($attribute = $entityColumn->getFirstAttributeByClass(Column::class)) {
            return $attribute;
        }
        $columnIsClass = $entityColumn instanceof CastableColumn || $entityColumn instanceof EntityColumn;
        if (!$columnIsClass) {
            return null;
        }
        $reflection = new \ReflectionClass($entityColumn->type);
        foreach ($reflection->getAttributes(Column::class) as $attribute) {
            return $attribute->newInstance();
        }
        return null;
    }
}