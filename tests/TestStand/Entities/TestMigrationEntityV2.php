<?php declare(strict_types=1);

namespace Composite\Sync\Tests\TestStand\Entities;

use Composite\Sync\Attributes\SkipMigration;
use Composite\DB\Attributes\{Table, PrimaryKey};
use Composite\Sync\Tests\TestStand\Entities\Castable\TestCastableIntObject;
use Composite\Sync\Tests\TestStand\Entities\Enums\TestBackedIntEnum;
use Composite\Sync\Tests\TestStand\Entities\Enums\TestUnitEnum;
use Composite\Entity\AbstractEntity;

#[Table(connection: 'mysql', name: 'EntityTestTable')]
#[SkipMigration]
class TestMigrationEntityV2 extends AbstractEntity
{
    #[PrimaryKey(autoIncrement: true)]
    public readonly int $id;

    public function __construct(
        public string $str_col,
        public int $int_col,
        public float $float_col,
        public bool $bool_col,
        public array $arr_col,
        public ?\stdClass $object = null,
        public ?TestSubEntity $entity = null,
        public \DateTime $date_time = new \DateTime(),
        public TestBackedIntEnum $backed_enum = TestBackedIntEnum::FooInt,
        public TestUnitEnum $unit_enum = TestUnitEnum::Bar,
        public TestCastableIntObject $castable = new TestCastableIntObject(946684801) //2000-01-01 00:00:01
    ) {}
}