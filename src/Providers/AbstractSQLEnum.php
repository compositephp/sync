<?php declare(strict_types=1);

namespace Composite\Sync\Providers;

abstract class AbstractSQLEnum
{
    public function __construct(
        public readonly string $name,
        public readonly array $values = [],
    ) {}
}