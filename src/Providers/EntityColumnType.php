<?php declare(strict_types=1);

namespace Composite\Sync\Providers;

use Composite\Entity\Schema;

enum EntityColumnType: string
{
    case String = 'string';
    case Integer = 'int';
    case Float = 'float';
    case Boolean = 'bool';
    case Datetime = '\DateTimeImmutable';
    case Array = 'array';
    case Object = '\stdClass';
    case Enum = 'enum';
}