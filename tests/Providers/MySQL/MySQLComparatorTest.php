<?php declare(strict_types=1);

namespace Composite\Sync\Tests\Providers\MySQL;

use Composite\Sync\Attributes\SkipMigration;
use Composite\DB\Attributes\{PrimaryKey, Table};
use Composite\Sync\Providers\MySQL\MySQLComparator;
use Composite\Sync\Providers\MySQL\MySQLIndex;
use Composite\Sync\Providers\MySQL\MySQLIndexType;
use Composite\Sync\Providers\MySQL\MySQLParser;
use Composite\Sync\Providers\MySQL\MySQLTable;
use Composite\DB\TableConfig;
use Composite\Entity\AbstractEntity;
use Composite\Sync\Attributes\Index;
use Composite\Sync\Tests\TestStand\Entities;

final class MySQLComparatorTest extends \PHPUnit\Framework\TestCase
{
    public function run_dataProvider(): array
    {
        $simpleEntity = new
        #[Table(connection: 'mysql', name: 'Foo')]
        class extends AbstractEntity {
            #[PrimaryKey(autoIncrement: true)]
            public readonly int $id;
            public string $bar2;
            #[SkipMigration]
            public string $skipColumn;

            public function __construct(
                public int $foo1 = 1,
                public string $bar1 = 'bar',
            ) {}
        };

        $entityWithIndex = new
        #[Table(connection: 'mysql', name: 'FooI')]
        #[Index(columns: ['name'], isUnique: true)]
        #[Index(columns: ['name', 'created_at'])]
        class(1, 'Test') extends AbstractEntity {
            public function __construct(
                #[PrimaryKey]
                public readonly int $id,
                public string $name,
                public readonly \DateTimeImmutable $created_at = new \DateTimeImmutable(),
            ) {}
        };

        $nullableEntity = new
        #[Table(connection: 'mysql', name: 'Foo')]
        class('a', null) extends AbstractEntity {
            public function __construct(
                #[PrimaryKey]
                public readonly string $id,
                public ?string $str1,
                public ?string $str2 = null,
            ) {}
        };

        return [
            [
                'entity' => $simpleEntity,
                'sql' => null,
                'expectedNewColumns' => ['id', 'bar2', 'foo1', 'bar1'],
                'expectedChangedColumns' => [],
                'expectedNewIndexes' => [],
                'expectedDeletedIndexes' => [],
                'expectedPrimaryKeyChanged' => false,
                'expectedUpQueries' => [
                    <<<SQL
                        CREATE TABLE `Foo` (
                            `id` INT NOT NULL AUTO_INCREMENT,
                            `bar2` VARCHAR(255) NOT NULL,
                            `foo1` INT NOT NULL DEFAULT 1,
                            `bar1` VARCHAR(255) NOT NULL DEFAULT 'bar',
                            PRIMARY KEY (`id`)
                       ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci;
                    SQL,
                ],
                'expectedDownQueries' => [
                    'DROP TABLE IF EXISTS `Foo`;'
                ],
            ],
            [
                'entity' => $simpleEntity,
                'sql' => <<<SQL
                    CREATE TABLE `Foo` (
                        `id` INT NOT NULL AUTO_INCREMENT,
                        `bar2` VARCHAR(255) NOT NULL,
                        `foo1` INT NOT NULL DEFAULT 1,
                        `bar1` VARCHAR(255) NOT NULL DEFAULT 'bar',
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci;
                SQL,
                'expectedNewColumns' => [],
                'expectedChangedColumns' => [],
                'expectedNewIndexes' => [],
                'expectedDeletedIndexes' => [],
                'expectedPrimaryKeyChanged' => false,
                'expectedUpQueries' => [],
                'expectedDownQueries' => [],
            ],
            [
                'entity' => $simpleEntity,
                'sql' =>
                    <<<SQL
                        CREATE TABLE `Foo` (
                            `id` INT NOT NULL AUTO_INCREMENT,
                            `foo1` INT NOT NULL DEFAULT 1,
                            `bar1` INT NOT NULL,
                            PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci;
                    SQL,
                'expectedNewColumns' => ['bar2'],
                'expectedChangedColumns' => ['bar1'],
                'expectedNewIndexes' => [],
                'expectedDeletedIndexes' => [],
                'expectedPrimaryKeyChanged' => false,
                'expectedUpQueries' => [
                    <<<SQL
                        ALTER TABLE `Foo`
                        ADD `bar2` VARCHAR(255) NOT NULL,
                        MODIFY `bar1` VARCHAR(255) NOT NULL DEFAULT 'bar';
                    SQL
                ],
                'expectedDownQueries' => [
                    <<<SQL
                        ALTER TABLE `Foo` MODIFY `bar1` INT NOT NULL, DROP COLUMN `bar2`;
                    SQL
                ],
            ],
            [
                'entity' => $entityWithIndex,
                'sql' => null,
                'expectedNewColumns' => ['id', 'name', 'created_at'],
                'expectedChangedColumns' => [],
                'expectedNewIndexes' => [
                    new MySQLIndex(
                        type: MySQLIndexType::UNIQUE,
                        name: 'FooI_unq_name',
                        columns: ['name'],
                        isUnique: true,
                    ),
                    new MySQLIndex(
                        type: MySQLIndexType::INDEX,
                        name: 'FooI_idx_name_created_at',
                        columns: ['name', 'created_at'],
                        isUnique: false,
                    ),
                ],
                'expectedDeletedIndexes' => [],
                'expectedPrimaryKeyChanged' => false,
                'expectedUpQueries' => [
                    <<<SQL
                        CREATE TABLE `FooI` (
                            `id` INT NOT NULL,
                            `name` VARCHAR(255) NOT NULL,
                            `created_at` TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `FooI_unq_name` (`name`),
                            KEY `FooI_idx_name_created_at` (`name`,`created_at`)
                        ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci;
                    SQL,
                ],
                'expectedDownQueries' => [
                    'DROP TABLE IF EXISTS `FooI`;'
                ],
            ],
            [
                'entity' => $entityWithIndex,
                'sql' => <<<SQL
                    CREATE TABLE `FooI` (
                        `id` INT NOT NULL,
                        `name` VARCHAR(255) NOT NULL,
                        `created_at` TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `FooI_unq_name` (`name`),
                        KEY `FooI_idx_name_created_at` (`name`,`created_at`)
                    ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci;
                SQL,
                'expectedNewColumns' => [],
                'expectedChangedColumns' => [],
                'expectedNewIndexes' => [],
                'expectedDeletedIndexes' => [],
                'expectedPrimaryKeyChanged' => false,
                'expectedUpQueries' => [],
                'expectedDownQueries' => [],
            ],
            [
                'entity' => $entityWithIndex,
                'sql' => <<<SQL
                    CREATE TABLE `FooI` (
                        `id` INT NOT NULL,
                        `name` VARCHAR(255) NOT NULL,
                        `created_at` TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                        PRIMARY KEY (`name`),
                        KEY `FooI_idx_created_at` (`created_at`)
                    ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci;
                SQL,
                'expectedNewColumns' => [],
                'expectedChangedColumns' => [],
                'expectedNewIndexes' => [
                    new MySQLIndex(
                        type: MySQLIndexType::UNIQUE,
                        name: 'FooI_unq_name',
                        columns: ['name'],
                        isUnique: true,
                    ),
                    new MySQLIndex(
                        type: MySQLIndexType::INDEX,
                        name: 'FooI_idx_name_created_at',
                        columns: ['name', 'created_at'],
                        isUnique: false,
                    ),
                ],
                'expectedDeletedIndexes' => [
                    new MySQLIndex(
                        type: MySQLIndexType::INDEX,
                        name: 'FooI_idx_created_at',
                        columns: ['created_at'],
                        isUnique: false,
                    ),
                ],
                'expectedPrimaryKeyChanged' => true,
                'expectedUpQueries' => [
                    'ALTER TABLE `FooI` DROP PRIMARY KEY, ADD PRIMARY KEY(`id`);',
                    'DROP INDEX `FooI_idx_created_at` ON `FooI`;',
                    'CREATE UNIQUE KEY `FooI_unq_name` ON `FooI` (`name`);',
                    'CREATE INDEX `FooI_idx_name_created_at` ON `FooI` (`name`,`created_at`);',
                ],
                'expectedDownQueries' => [
                    'ALTER TABLE `FooI` DROP PRIMARY KEY, ADD PRIMARY KEY(`name`);',
                    'DROP INDEX `FooI_unq_name` ON `FooI`;',
                    'DROP INDEX `FooI_idx_name_created_at` ON `FooI`;',
                    'CREATE INDEX `FooI_idx_created_at` ON `FooI` (`created_at`);',
                ],
            ],
            [
                'entity' => $entityWithIndex,
                'sql' => <<<SQL
                    CREATE TABLE `FooI` (
                        `id` INT NOT NULL,
                        `name` VARCHAR(128) NOT NULL,
                        `created_at` TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `FooI_unq_name` (`name`),
                        KEY `FooI_idx_created_name_at` (`created_at`, `name`)
                    ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci;
                SQL,
                'expectedNewColumns' => [],
                'expectedChangedColumns' => ['name'],
                'expectedNewIndexes' => [
                    new MySQLIndex(
                        type: MySQLIndexType::INDEX,
                        name: 'FooI_idx_name_created_at',
                        columns: ['name', 'created_at'],
                        isUnique: false,
                    ),
                ],
                'expectedDeletedIndexes' => [
                    new MySQLIndex(
                        type: MySQLIndexType::INDEX,
                        name: 'FooI_idx_created_name_at',
                        columns: ['created_at', 'name'],
                        isUnique: false,
                    ),
                ],
                'expectedPrimaryKeyChanged' => false,
                'expectedUpQueries' => [
                    'ALTER TABLE `FooI` MODIFY `name` VARCHAR(255) NOT NULL;',
                    'DROP INDEX `FooI_idx_created_name_at` ON `FooI`;',
                    'CREATE INDEX `FooI_idx_name_created_at` ON `FooI` (`name`,`created_at`);',
                ],
                'expectedDownQueries' => [
                    'ALTER TABLE `FooI` MODIFY `name` VARCHAR(128) NOT NULL;',
                    'DROP INDEX `FooI_idx_name_created_at` ON `FooI`;',
                    'CREATE INDEX `FooI_idx_created_name_at` ON `FooI` (`created_at`,`name`);'
                ],
            ],
            [
                'entity' => new Entities\TestCompositeEntity(1, 2, 'a'),
                'sql' => null,
                'expectedNewColumns' => ['user_id', 'post_id', 'message', 'created_at'],
                'expectedChangedColumns' => [],
                'expectedNewIndexes' => [],
                'expectedDeletedIndexes' => [],
                'expectedPrimaryKeyChanged' => false,
                'expectedUpQueries' => [
                    <<<SQL
                        CREATE TABLE `TestComposite` (
                            `user_id` INT NOT NULL,
                            `post_id` INT NOT NULL,
                            `message` VARCHAR(255) NOT NULL,
                            `created_at` TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                            PRIMARY KEY (`user_id`,`post_id`)
                        ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci;
                    SQL,
                ],
                'expectedDownQueries' => [
                    'DROP TABLE IF EXISTS `TestComposite`;'
                ],
            ],
            [
                'entity' => new Entities\TestOptimisticLockEntity( 'a'),
                'sql' => null,
                'expectedNewColumns' => ['id', 'name', 'created_at', 'version'],
                'expectedChangedColumns' => [],
                'expectedNewIndexes' => [],
                'expectedDeletedIndexes' => [],
                'expectedPrimaryKeyChanged' => false,
                'expectedUpQueries' => [
                    <<<SQL
                        CREATE TABLE `TestOptimisticLock` (
                            `id` INT NOT NULL AUTO_INCREMENT,
                            `name` VARCHAR(255) NOT NULL,
                            `created_at` TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                            `version` INT NOT NULL DEFAULT 1,
                            PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci;
                    SQL,
                ],
                'expectedDownQueries' => [
                    'DROP TABLE IF EXISTS `TestOptimisticLock`;'
                ],
            ],
            [
                'entity' => new Entities\TestDiversityEntity(
                    obj1: new \stdClass(),
                    obj2: null,
                    dt1: new \DateTime(),
                    dt2: null,
                    dti1: new \DateTimeImmutable(),
                    dti2: null,
                    entity1: new Entities\TestSubEntity(),
                    entity2: null,
                    castable_int1: new Entities\Castable\TestCastableIntObject(1),
                    castable_int2: null,
                    castable_str1: new Entities\Castable\TestCastableStringObject(null),
                    castable_str2: null,
                ),
                'sql' => null,
                'expectedNewColumns' => [
                    'id',
                    'str1',
                    'str2',
                    'str3',
                    'str4',
                    'str5',
                    'int1',
                    'int2',
                    'int3',
                    'int4',
                    'int5',
                    'float1',
                    'float2',
                    'float3',
                    'float4',
                    'float5',
                    'bool1',
                    'bool2',
                    'bool3',
                    'bool4',
                    'bool5',
                    'arr1',
                    'arr2',
                    'arr3',
                    'arr4',
                    'arr5',
                    'benum_str1',
                    'benum_str2',
                    'benum_str3',
                    'benum_str4',
                    'benum_str5',
                    'benum_int1',
                    'benum_int2',
                    'benum_int3',
                    'benum_int4',
                    'benum_int5',
                    'uenum1',
                    'uenum2',
                    'uenum3',
                    'uenum4',
                    'uenum5',
                    'obj1',
                    'obj2',
                    'dt1',
                    'dt2',
                    'dti1',
                    'dti2',
                    'entity1',
                    'entity2',
                    'castable_int1',
                    'castable_int2',
                    'castable_str1',
                    'castable_str2',
                    'obj3',
                    'obj4',
                    'obj5',
                    'dt3',
                    'dt4',
                    'dt5',
                    'dti3',
                    'dti4',
                    'dti5',
                    'entity3',
                    'entity4',
                    'entity5',
                    'castable_int3',
                    'castable_int4',
                    'castable_int5',
                    'castable_str3',
                    'castable_str4',
                    'castable_str5',
                ],
                'expectedChangedColumns' => [],
                'expectedNewIndexes' => [],
                'expectedDeletedIndexes' => [],
                'expectedPrimaryKeyChanged' => false,
                'expectedUpQueries' => [
                    <<<SQL
                        CREATE TABLE `Diversity` ( `id` INT NOT NULL AUTO_INCREMENT,
                            `str1` VARCHAR(255) NOT NULL,
                            `str2` VARCHAR(255) NULL,
                            `str3` VARCHAR(255) NOT NULL DEFAULT 'str3 def',
                            `str4` VARCHAR(255) NULL DEFAULT '',
                            `str5` VARCHAR(255) NULL DEFAULT NULL,
                            `int1` INT NOT NULL,
                            `int2` INT NULL,
                            `int3` INT NOT NULL DEFAULT 33,
                            `int4` INT NULL DEFAULT 44,
                            `int5` INT NULL DEFAULT NULL,
                            `float1` FLOAT NOT NULL,
                            `float2` FLOAT NULL,
                            `float3` FLOAT NOT NULL DEFAULT 3.9,
                            `float4` FLOAT NULL DEFAULT 4.9,
                            `float5` FLOAT NULL DEFAULT NULL,
                            `bool1` TINYINT(1) NOT NULL,
                            `bool2` TINYINT(1) NULL,
                            `bool3` TINYINT(1) NOT NULL DEFAULT 1,
                            `bool4` TINYINT(1) NULL DEFAULT 0,
                            `bool5` TINYINT(1) NULL DEFAULT NULL,
                            `arr1` JSON NOT NULL,
                            `arr2` JSON NULL,
                            `arr3` JSON NOT NULL,
                            `arr4` JSON NULL DEFAULT NULL,
                            `arr5` JSON NULL DEFAULT NULL,
                            `benum_str1` ENUM('foo','bar') NOT NULL,
                            `benum_str2` ENUM('foo','bar') NULL,
                            `benum_str3` ENUM('foo','bar') NOT NULL DEFAULT 'foo',
                            `benum_str4` ENUM('foo','bar') NULL DEFAULT 'bar',
                            `benum_str5` ENUM('foo','bar') NULL DEFAULT NULL,
                            `benum_int1` INT NOT NULL,
                            `benum_int2` INT NULL,
                            `benum_int3` INT NOT NULL DEFAULT 123,
                            `benum_int4` INT NULL DEFAULT 456,
                            `benum_int5` INT NULL DEFAULT NULL,
                            `uenum1` ENUM('Foo','Bar') NOT NULL,
                            `uenum2` ENUM('Foo','Bar') NULL,
                            `uenum3` ENUM('Foo','Bar') NOT NULL DEFAULT 'Foo',
                            `uenum4` ENUM('Foo','Bar') NULL DEFAULT 'Bar',
                            `uenum5` ENUM('Foo','Bar') NULL DEFAULT NULL,
                            `obj1` JSON NOT NULL,
                            `obj2` JSON NULL,
                            `dt1` TIMESTAMP(6) NOT NULL,
                            `dt2` TIMESTAMP(6) NULL,
                            `dti1` TIMESTAMP(6) NOT NULL,
                            `dti2` TIMESTAMP(6) NULL,
                            `entity1` JSON NOT NULL,
                            `entity2` JSON NULL,
                            `castable_int1` VARCHAR(255) NOT NULL,
                            `castable_int2` VARCHAR(255) NULL,
                            `castable_str1` VARCHAR(255) NOT NULL,
                            `castable_str2` VARCHAR(255) NULL,
                            `obj3` JSON NOT NULL,
                            `obj4` JSON NULL DEFAULT NULL,
                            `obj5` JSON NULL DEFAULT NULL,
                            `dt3` TIMESTAMP(6) NOT NULL DEFAULT '2000-01-01 00:00:00.000000',
                            `dt4` TIMESTAMP(6) NULL DEFAULT CURRENT_TIMESTAMP(6),
                            `dt5` TIMESTAMP(6) NULL DEFAULT NULL,
                            `dti3` TIMESTAMP(6) NOT NULL DEFAULT '2000-01-01 00:00:00.000000',
                            `dti4` TIMESTAMP(6) NULL DEFAULT CURRENT_TIMESTAMP(6),
                            `dti5` TIMESTAMP(6) NULL DEFAULT NULL,
                            `entity3` JSON NOT NULL,
                            `entity4` JSON NULL DEFAULT NULL,
                            `entity5` JSON NULL DEFAULT NULL,
                            `castable_int3` VARCHAR(255) NOT NULL DEFAULT 946684801,
                            `castable_int4` VARCHAR(255) NULL DEFAULT 946684802,
                            `castable_int5` VARCHAR(255) NULL DEFAULT NULL,
                            `castable_str3` VARCHAR(255) NOT NULL DEFAULT '_Hello_',
                            `castable_str4` VARCHAR(255) NULL DEFAULT '_World_',
                            `castable_str5` VARCHAR(255) NULL DEFAULT NULL,
                            PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci;
                    SQL,
                ],
                'expectedDownQueries' => [
                    'DROP TABLE IF EXISTS `Diversity`;'
                ],
            ],
            [
                'entity' => $nullableEntity,
                'sql' => null,
                'expectedNewColumns' => ['id', 'str1', 'str2'],
                'expectedChangedColumns' => [],
                'expectedNewIndexes' => [],
                'expectedDeletedIndexes' => [],
                'expectedPrimaryKeyChanged' => false,
                'expectedUpQueries' => [
                    <<<SQL
                        CREATE TABLE `Foo` (
                            `id` VARCHAR(255) NOT NULL,
                            `str1` VARCHAR(255) NULL,
                            `str2` VARCHAR(255) NULL DEFAULT NULL,
                            PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci;
                    SQL,
                ],
                'expectedDownQueries' => [
                    'DROP TABLE IF EXISTS `Foo`;'
                ],
            ],
            [
                'entity' => $nullableEntity,
                'sql' => <<<SQL
                    CREATE TABLE `Foo` (
                        `id` VARCHAR(255) NOT NULL,
                        `str1` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
                        `str2` VARCHAR(255) NULL DEFAULT NULL,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci;
                SQL,
                'expectedNewColumns' => [],
                'expectedChangedColumns' => [],
                'expectedNewIndexes' => [],
                'expectedDeletedIndexes' => [],
                'expectedPrimaryKeyChanged' => false,
                'expectedUpQueries' => [],
                'expectedDownQueries' => [],
            ],
            [
                'entity' => $nullableEntity,
                'sql' => <<<SQL
                    CREATE TABLE `Foo` ( 
                        `id` VARCHAR(255) NOT NULL, 
                        `str1` VARCHAR(255) NULL DEFAULT NULL, 
                        `str2` VARCHAR(255) NULL, 
                        PRIMARY KEY (`id`) 
                    ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci;
                SQL,
                'expectedNewColumns' => [],
                'expectedChangedColumns' => [],
                'expectedNewIndexes' => [],
                'expectedDeletedIndexes' => [],
                'expectedPrimaryKeyChanged' => false,
                'expectedUpQueries' => [],
                'expectedDownQueries' => [],
            ],
        ];
    }

    /**
     * @dataProvider run_dataProvider
     */
    public function test_run(
        AbstractEntity $entity,
        ?string $sql,
        array $expectedNewColumns,
        array $expectedChangedColumns,
        array $expectedNewIndexes,
        array $expectedDeletedIndexes,
        bool $expectedPrimaryKeyChanged,
        array $expectedUpQueries,
        array $expectedDownQueries,
    ): void
    {
        $schema = $entity::schema();
        $tableConfig = TableConfig::fromEntitySchema($schema);
        $comparator = new MySQLComparator(
            $tableConfig->connectionName,
            entityTable: MySQLTable::fromEntitySchema($schema),
            databaseTable: $sql ? (new MySQLParser($tableConfig->tableName, $sql))->getSQLTable() : null,
        );
        $this->assertEquals($expectedNewColumns, $comparator->newColumns, 'newColumns are not equal');
        $this->assertEquals($expectedChangedColumns, $comparator->changedColumns, 'changedColumns are not equal');
        $this->assertEquals($expectedNewIndexes, $comparator->newIndexes, 'newIndexes are not equal');
        $this->assertEquals($expectedDeletedIndexes, $comparator->deletedIndexes, 'deletedIndexes are not equal');
        $this->assertEquals($expectedPrimaryKeyChanged, $comparator->primaryKeyChanged, 'primaryKeyChanged are not equal');

        $this->compareQueries($expectedUpQueries, $comparator->getUpQueries());
        $this->compareQueries($expectedDownQueries, $comparator->getDownQueries());
    }

    private function compareQueries(array $expected, array $actual): void
    {
        $expected = array_values(array_filter($expected));
        $actual = array_values(array_filter($actual));
        if (!$expected && !$actual) {
            return;
        }
        array_walk($expected, [$this, 'normalizeSqlString']);
        array_walk($actual, [$this, 'normalizeSqlString']);

        $this->assertSame($expected, $actual);
    }

    private function normalizeSqlString(string &$sql): string
    {
        $sql = str_replace(["\r\n", "\r"], "\n", $sql);
        $sql = str_replace(["\n", "\t"], " ", $sql);
        $sql = preg_replace('/\s+/', " ", $sql);
        $sql = trim($sql);
        return $sql;
    }
}
