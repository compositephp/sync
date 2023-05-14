<?php declare(strict_types=1);

namespace Composite\Sync\Providers\SQLite;

use Composite\Sync\Providers\AbstractComparator;

class SQLiteComparator extends AbstractComparator
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