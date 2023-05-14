<?php declare(strict_types=1);

namespace Composite\Sync\Tests\TestStand\Entities;

use Composite\Sync\Attributes\SkipMigration;
use Composite\DB\Attributes\{Table, PrimaryKey};
use Composite\Entity\AbstractEntity;

#[Table(connection: 'mysql', name: 'EntityTestTable')]
#[SkipMigration]
class TestMigrationEntityV1 extends AbstractEntity
{
    #[PrimaryKey(autoIncrement: true)]
    public readonly int $id;

    public function __construct(
        public string $str_col,
        public int $int_col,
        public float $float_col,
        public bool $bool_col,
        public array $arr_col,
    ) {}
}