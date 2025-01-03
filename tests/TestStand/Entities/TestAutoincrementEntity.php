<?php declare(strict_types=1);

namespace Composite\Sync\Tests\TestStand\Entities;

use Composite\Entity\AbstractEntity;
use Composite\DB\Attributes\{PrimaryKey};
use Composite\DB\Attributes\Table;

#[Table(connection: 'mysql', name: 'TestAutoincrement')]
class TestAutoincrementEntity extends AbstractEntity
{
    #[PrimaryKey(autoIncrement: true)]
    public readonly int $id;

    public function __construct(
        public string $name,
        public readonly \DateTimeImmutable $created_at = new \DateTimeImmutable(),
    ) {}
}