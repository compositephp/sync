<?php declare(strict_types=1);

namespace Composite\Sync\Tests\Migration;

use Composite\DB\ConnectionManager;
use Composite\Sync\Migration\MigrationsManager;
use Composite\Sync\Migration\MigrationVersionTable;
use Composite\Sync\Providers\SQLTableFactory;
use Composite\DB\TableConfig;
use Composite\Sync\Tests\TestStand\Entities\TestMigrationEntityV1;
use Composite\Sync\Tests\TestStand\Entities\TestMigrationEntityV2;
use Composite\Sync\Tests\TestStand\Tables\TestMySQLTable;
use Doctrine\DBAL\Exception\TableNotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;

final class MigrationManagerTest extends \PHPUnit\Framework\TestCase
{
    #[DataProvider('migrationName_dataProvider')]
    public function test_buildMigrationName(string $connectionName, array $summaryParts, string $expectedResult)
    {
        $migrationName = MigrationsManager::buildMigrationName($connectionName, $summaryParts);
        $this->assertMatchesRegularExpression('/^'.$expectedResult . '$/', $migrationName);
    }

    public static function migrationName_dataProvider(): array
    {
        $prefix = 'Migration_(\d{12})';

        return [
            'No summary parts' => [
                'connectionName' => 'conn1',
                'summaryParts' => [],
                'expectedResult' => $prefix . '_conn1',
            ],
            'One summary part' => [
                'connectionName' => 'conn2',
                'summaryParts' => ['part1'],
                'expectedResult' => $prefix . '_conn2_part1',
            ],
            'Multiple summary parts' => [
                'connectionName' => 'conn3',
                'summaryParts' => ['part1', 'part2', 'part3'],
                'expectedResult' => $prefix . '_conn3_part1_part2_part3',
            ],
            'Summary parts exceeding limit' => [
                'connectionName' => 'conn4',
                'summaryParts' => array_fill(0, 30, 'longpart'),
                'expectedResult' => $prefix . '_conn4_longpart_longpart_longpart_longpart_longpart_longpart_longpart_longpart_longpart_longpart_longpart_longpart_longpart_longpart_longpart_longpart_longpart_longpart_longpart_longpart_and_10_more',
            ],
        ];
    }

    public static function flow_dataProvider(): array
    {
        return [
            ['mysql'],
        ];
    }

    #[DataProvider('flow_dataProvider')]
    public function test_flow(string $connectionName): void
    {
        $this->cleanVersionTable($connectionName);
        $this->cleanMigrationFiles();

        $manager = new MigrationsManager();
        $manager->scanMigrationsDirectory();

        $lastMigrations = $manager->getLastExecutedMigrations($connectionName, 10);

        $this->assertEmpty($lastMigrations);
        $this->assertEmpty($manager->getWaitingMigrations());

        $classBuilder1 = $manager->checkClass(TestMigrationEntityV1::class);
        $this->assertNotNull($classBuilder1);
        $this->assertNotEmpty($classBuilder1->getFileContent());

        $classBuilder1->save();

        $manager->scanMigrationsDirectory();
        $waitingMigrations = $manager->getWaitingMigrations();
        $this->assertCount(1, $waitingMigrations);

        $migrationName1 = $waitingMigrations[0];
        $manager->executeMigration($migrationName1);

        $lastMigrations = $manager->getLastExecutedMigrations($connectionName, 10);
        $this->assertNotEmpty($lastMigrations);
        $this->assertEquals($migrationName1, $lastMigrations[0]);

        $this->assertCount(0, $manager->getWaitingMigrations());
        $this->assertNull($manager->checkClass(TestMigrationEntityV1::class));

        $table = new TestMySQLTable();

        $sqlTable = SQLTableFactory::parseFromDatabase($connectionName, $table->getTableName());
        $this->assertNotEmpty($sqlTable);
        foreach (TestMigrationEntityV1::schema()->columns as $column) {
            $this->assertNotEmpty($sqlTable->getColumnByName($column->name));
        }

        $entity = new TestMigrationEntityV1(
            str_col: 'foo',
            int_col: 123,
            float_col: 456.001,
            bool_col: true,
            arr_col: ['a', 'b', 'c'],
        );
        $table->save($entity);
        $this->assertNotNull($table->findByPk($entity->id));

        $classBuilder2 = $manager->checkClass(TestMigrationEntityV2::class);
        $this->assertNotNull($classBuilder2);
        $this->assertNotEmpty($classBuilder2->getFileContent());

        $classBuilder2->save();

        $manager->scanMigrationsDirectory();
        $waitingMigrations = $manager->getWaitingMigrations();
        $this->assertCount(1, $waitingMigrations);

        $migrationName2 = $waitingMigrations[0];
        $manager->executeMigration($migrationName2);
        
        $this->assertCount(0, $manager->getWaitingMigrations());
        $this->assertNull($manager->checkClass(TestMigrationEntityV2::class));

        $sqlTable = SQLTableFactory::parseFromDatabase($connectionName, $table->getTableName());
        $this->assertNotEmpty($sqlTable);
        foreach (TestMigrationEntityV2::schema()->columns as $column) {
            $this->assertNotEmpty($sqlTable->getColumnByName($column->name));
        }

        $manager->rollbackMigration($migrationName2);
        $this->assertCount(1, $manager->getWaitingMigrations());
        $this->assertNotNull($manager->checkClass(TestMigrationEntityV2::class));

        $this->assertNotNull($table->findByPk($entity->id));

        $sqlTable = SQLTableFactory::parseFromDatabase($connectionName, $table->getTableName());
        $this->assertNotEmpty($sqlTable);

        $v1Columns = array_map(fn ($column) => $column->name, TestMigrationEntityV1::schema()->columns);
        $v2Columns = array_map(fn ($column) => $column->name, TestMigrationEntityV2::schema()->columns);
        $columnsDiff = array_diff($v2Columns, $v1Columns);
        $this->assertNotEmpty($columnsDiff);

        foreach ($columnsDiff as $columnName) {
            $this->assertEmpty($sqlTable->getColumnByName($columnName));
        }

        $manager->rollbackMigration($migrationName1);
        $this->assertCount(2, $manager->getWaitingMigrations());
        $this->assertNotNull($manager->checkClass(TestMigrationEntityV1::class));
        $this->assertNotNull($manager->checkClass(TestMigrationEntityV2::class));

        try {
            $table->findByPk($entity->id);
            $exceptionTriggered = false;
        } catch (TableNotFoundException) {
            $exceptionTriggered = true;
        }
        $this->assertTrue($exceptionTriggered);
    }

    private function cleanVersionTable(string $connectionName): void
    {
        $connection = ConnectionManager::getConnection($connectionName);
        $connection->executeQuery("DROP TABLE IF EXISTS `" . MigrationVersionTable::TABLE_NAME . "`");

        $tableConfig = TableConfig::fromEntitySchema(TestMigrationEntityV1::schema());
        $connection->executeQuery("DROP TABLE IF EXISTS `" . $tableConfig->tableName . "`");
    }

    private function cleanMigrationFiles(): void
    {
        $dir = MigrationsManager::getMigrationsDirectory();
        foreach (glob($dir . 'Migration_*.php') as $file) {
            unlink($file);
        }
    }
}