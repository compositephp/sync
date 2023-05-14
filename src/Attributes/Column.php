<?php declare(strict_types=1);

namespace Composite\Sync\Attributes;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public readonly ?string $type = null,
        public readonly string|int|float|bool|null $default = null,
        public readonly ?int $size = null,
        public readonly ?int $precision = null,
        public readonly ?int $scale = null,
        public readonly ?bool $unsigned = null,
        public readonly ?string $collation = null,
        public readonly ?string $onUpdate = null,
    ) {}
}
