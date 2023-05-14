<?php declare(strict_types=1);

namespace Composite\Sync\Tests\Generator;

use Composite\Sync\Generator\TableClassBuilder;
use Composite\Sync\Tests\TestStand\Entities\TestAutoincrementEntity;
use Composite\Sync\Tests\TestStand\Entities\TestCompositeEntity;

final class TableClassBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider classBuilder_dataProvider
     */
    public function test_getFileContent(string $tableClassName, string $entityClassName, $expectedOutput)
    {
        $classBuilder = new TableClassBuilder(
            tableClass: $tableClassName,
            entityClass: $entityClassName
        );

        $actualOutput = $classBuilder->getFileContent();
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function classBuilder_dataProvider(): array
    {
        return [
            [
                'Composite\Sync\Tests\Generator\Table',
                TestAutoincrementEntity::class,
                '<?php

declare(strict_types=1);

namespace Composite\Sync\Tests\Generator;

use Composite\DB\AbstractTable;
use Composite\DB\TableConfig;
use Composite\Sync\Tests\TestStand\Entities\TestAutoincrementEntity;

class Table extends AbstractTable
{
	protected function getConfig(): TableConfig
	{
		return TableConfig::fromEntitySchema(TestAutoincrementEntity::schema());
	}


	public function findByPk(int $id): ?TestAutoincrementEntity
	{
		return $this->createEntity($this->findByPkInternal($id));
	}


	/**
	 * @return TestAutoincrementEntity[]
	 */
	public function findAll(): array
	{
		return $this->createEntities($this->findAllInternal());
	}


	public function countAll(): int
	{
		return $this->countAllInternal();
	}
}
'
            ],
            [
                '\CompositeTable',
                TestCompositeEntity::class,
                '<?php

declare(strict_types=1);

use Composite\DB\AbstractTable;
use Composite\DB\TableConfig;
use Composite\Sync\Tests\TestStand\Entities\TestCompositeEntity;

class CompositeTable extends AbstractTable
{
	protected function getConfig(): TableConfig
	{
		return TableConfig::fromEntitySchema(TestCompositeEntity::schema());
	}


	public function findByPk(int $user_id, int $post_id): ?TestCompositeEntity
	{
		return $this->createEntity($this->findOneInternal([\'user_id\' => $user_id, \'post_id\' => $post_id]));
	}


	/**
	 * @return TestCompositeEntity[]
	 */
	public function findAll(): array
	{
		return $this->createEntities($this->findAllInternal());
	}


	public function countAll(): int
	{
		return $this->countAllInternal();
	}
}
'
            ],
        ];
    }
}