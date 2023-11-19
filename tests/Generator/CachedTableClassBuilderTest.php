<?php declare(strict_types=1);

namespace Composite\Sync\Tests\Generator;

use Composite\Sync\Generator\CachedTableClassBuilder;
use Composite\Sync\Tests\TestStand\Entities\TestAutoincrementEntity;
use Composite\Sync\Tests\TestStand\Entities\TestCompositeEntity;

final class CachedTableClassBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider classBuilder_dataProvider
     */
    public function test_getFileContent(string $tableClassName, string $entityClassName, $expectedOutput)
    {
        $classBuilder = new CachedTableClassBuilder(
            tableClass: $tableClassName,
            entityClass: $entityClassName
        );

        $actualOutput = $classBuilder->getFileContent();
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public static function classBuilder_dataProvider(): array
    {
        return [
            [
                'Composite\Sync\Tests\Generator\Table',
                TestAutoincrementEntity::class,
                '<?php

namespace Composite\Sync\Tests\Generator;

use Composite\DB\AbstractCachedTable;
use Composite\DB\TableConfig;
use Composite\Entity\AbstractEntity;
use Composite\Sync\Tests\TestStand\Entities\TestAutoincrementEntity;

class Table extends AbstractCachedTable
{
	protected function getConfig(): TableConfig
	{
		return TableConfig::fromEntitySchema(TestAutoincrementEntity::schema());
	}


	protected function getFlushCacheKeys(TestAutoincrementEntity|AbstractEntity $entity): array
	{
		return [
		    $this->getListCacheKey(),
		    $this->getCountCacheKey(),
		];
	}


	public function findByPk(int $id): ?TestAutoincrementEntity
	{
		return $this->_findByPkCached($id);
	}


	/**
	 * @return TestAutoincrementEntity[]
	 */
	public function findAll(): array
	{
		return $this->_findAllCached();
	}


	public function countAll(): int
	{
		return $this->_countAllCached();
	}
}
'
            ],
            [
                '\CompositeTable',
                TestCompositeEntity::class,
                '<?php

use Composite\DB\AbstractCachedTable;
use Composite\DB\TableConfig;
use Composite\Entity\AbstractEntity;
use Composite\Sync\Tests\TestStand\Entities\TestCompositeEntity;

class CompositeTable extends AbstractCachedTable
{
	protected function getConfig(): TableConfig
	{
		return TableConfig::fromEntitySchema(TestCompositeEntity::schema());
	}


	protected function getFlushCacheKeys(TestCompositeEntity|AbstractEntity $entity): array
	{
		return [
		    $this->getListCacheKey(),
		    $this->getCountCacheKey(),
		];
	}


	public function findByPk(int $user_id, int $post_id): ?TestCompositeEntity
	{
		return $this->_findOneCached([\'user_id\' => $user_id, \'post_id\' => $post_id]);
	}


	/**
	 * @return TestCompositeEntity[]
	 */
	public function findAll(): array
	{
		return $this->_findAllCached();
	}


	public function countAll(): int
	{
		return $this->_countAllCached();
	}
}
'
            ],
        ];
    }
}