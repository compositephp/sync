<?php declare(strict_types=1);

namespace Composite\Sync\Attributes;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS)]
class Index
{
    public function __construct(
        public readonly array $columns,
        public readonly bool $isUnique = false,
        public readonly ?array $sort = [],
        public readonly ?string $name = null,
    ) {}
}