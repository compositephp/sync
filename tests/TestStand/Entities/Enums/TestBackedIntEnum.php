<?php declare(strict_types=1);

namespace Composite\Sync\Tests\TestStand\Entities\Enums;

enum TestBackedIntEnum: int
{
    case FooInt = 123;
    case BarInt = 456;
}