<?php declare(strict_types=1);

namespace Composite\Sync\Providers\PostgreSQL;

use Composite\Sync\Providers\AbstractComparator;

class PgSQLComparator extends AbstractComparator
{
    public function getUpQueries(): array
    {
        throw new \Exception('Not Implemented');
    }

    public function getDownQueries(): array
    {
        throw new \Exception('Not Implemented');
    }
}