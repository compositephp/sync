<?php declare(strict_types=1);

namespace Composite\Sync\Tests\Migration;

use Composite\Sync\Migration\MigrationClassBuilder;

final class MigrationClassBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider migrationName_dataProvider
     */
    public function test_buildMigrationName(string $connectionName, array $summaryParts, array $upQueries, array $downQueries, string $expectedResult)
    {
        $builder = new MigrationClassBuilder(
            connectionName: $connectionName,
            summaryParts: $summaryParts,
            upQueries: $upQueries,
            downQueries: $downQueries
        );
        $this->assertEquals($expectedResult, $builder->getFileContent());
    }

    public function migrationName_dataProvider(): array
    {
        $timestamp = date('ymdhis');
        $prefix = 'Migration_' . $timestamp;

        return [
            'Happy case' => [
                'connectionName' => 'conn1',
                'summaryParts' => ['part1', 'part2', 'part3'],
                'upQueries' => ['SELECT *', 'UPDATE *'],
                'downQueries' => ['DELETE *'],
                'expectedResult' => '<?php

declare(strict_types=1);

namespace Composite\Sync\Tests\runtime\migrations;

class '. $prefix . '_conn1_part1_part2_part3 extends \Composite\Sync\Migration\AbstractMigration
{
	public const CONNECTION_NAME = \'conn1\';

	public function up(): void
	{
		$this->query("
			SELECT *
		");
		$this->query("
			UPDATE *
		");
	}


	public function down(): void
	{
		$this->query("
			DELETE *
		");
	}
}
',
                ],
                'Empty Down' => [
                    'connectionName' => 'conn2',
                    'summaryParts' => ['part1'],
                    'upQueries' => ['DELETE TABLE'],
                    'downQueries' => [],
                    'expectedResult' => '<?php

declare(strict_types=1);

namespace Composite\Sync\Tests\runtime\migrations;

class '. $prefix . '_conn2_part1 extends \Composite\Sync\Migration\AbstractMigration
{
	public const CONNECTION_NAME = \'conn2\';

	public function up(): void
	{
		$this->query("
			DELETE TABLE
		");
	}


	public function down(): void
	{
	}
}
',
                 ],
        ];
    }
}