<?php declare(strict_types=1);

namespace Composite\Sync\Migration;

use Composite\Sync\Attributes\Column;
use Composite\DB\Attributes\PrimaryKey;
use Composite\Entity\AbstractEntity;

class MigrationVersion extends AbstractEntity
{
    public function __construct(
        #[PrimaryKey]
        #[Column(size: 255)]
        public readonly string $version,
        public readonly \DateTimeImmutable $executed_at = new \DateTimeImmutable(),
    ) {}
}