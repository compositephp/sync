<?php declare(strict_types=1);

namespace Composite\Sync\Tests\TestStand\Tables;

use Composite\DB\AbstractTable;
use Composite\DB\TableConfig;
use Composite\Sync\Tests\TestStand\Entities\TestMigrationEntityV1;

class TestMySQLTable extends AbstractTable
{
    protected function getConfig(): TableConfig
    {
        return TableConfig::fromEntitySchema(TestMigrationEntityV1::schema());
    }

    public function findByPk(int $id): ?TestMigrationEntityV1
    {
        return $this->createEntity($this->findByPkInternal($id));
    }
}