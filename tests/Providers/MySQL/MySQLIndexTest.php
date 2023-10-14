<?php declare(strict_types=1);

namespace Composite\Sync\Tests\Providers\MySQL;

use Composite\Sync\Providers\MySQL\MySQLColumn;
use Composite\Sync\Providers\MySQL\MySQLColumnType;
use Composite\Sync\Providers\MySQL\MySQLIndex;
use Composite\Sync\Providers\MySQL\MySQLIndexType;
use iamcal\SQLParser;

final class MySQLIndexTest extends \PHPUnit\Framework\TestCase
{
    public static function index_dataProvider(): array
    {
        return [
            [
                "KEY `example1_idx` (`column1`,`column2`)",
                new MySQLIndex(
                    type: MySQLIndexType::INDEX,
                    name: 'example1_idx',
                    columns: ['column1', 'column2'],
                    isUnique: false,
                )
            ],
            [
                "UNIQUE KEY `example2_uindex` (`column3`)",
                new MySQLIndex(
                    type: MySQLIndexType::UNIQUE,
                    name: 'example2_uindex',
                    columns: ['column3'],
                    isUnique: true,
                )
            ],
            [
                "FULLTEXT KEY `example3_ftindex` (`column4`)",
                new MySQLIndex(
                    type: MySQLIndexType::FULLTEXT,
                    name: 'example3_ftindex',
                    columns: ['column4'],
                    isUnique: false,
                )
            ],
            [
                "PRIMARY KEY (`column5`)",
                new MySQLIndex(
                    type: MySQLIndexType::PRIMARY,
                    name: '',
                    columns: ['column5'],
                    isUnique: true,
                )
            ],
            [
                "KEY `example4_idx` (`column6` DESC,`column7` ASC)",
                new MySQLIndex(
                    type: MySQLIndexType::INDEX,
                    name: 'example4_idx',
                    columns: ['column6', 'column7'],
                    isUnique: false,
                    order: ['column6' => 'DESC', 'column7' => 'ASC']
                )
            ],
            [
                "UNIQUE KEY `example5_uindex` (`column8`,`column9`)",
                new MySQLIndex(
                    type: MySQLIndexType::UNIQUE,
                    name: 'example5_uindex',
                    columns: ['column8', 'column9'],
                    isUnique: true,
                    order: []
                )
            ],
            [
                "FULLTEXT KEY `example6_ftindex` (`column10`,`column11`)",
                new MySQLIndex(
                    type: MySQLIndexType::FULLTEXT,
                    name: 'example6_ftindex',
                    columns: ['column10', 'column11'],
                    isUnique: false,
                )
            ],
            [
                "PRIMARY KEY (`column12`,`column13`)",
                new MySQLIndex(
                    type: MySQLIndexType::PRIMARY,
                    name: '',
                    columns: ['column12', 'column13'],
                    isUnique: true,
                )
            ],
            [
                "KEY `example7_idx` (`column14`)",
                new MySQLIndex(
                    type: MySQLIndexType::INDEX,
                    name: 'example7_idx',
                    columns: ['column14'],
                    isUnique: false,
                )
            ],
            [
                "UNIQUE KEY `example8_uindex` (`column15` DESC)",
                new MySQLIndex(
                    type: MySQLIndexType::UNIQUE,
                    name: 'example8_uindex',
                    columns: ['column15'],
                    isUnique: true,
                    order: ['column15' => 'DESC']
                )
            ]
        ];
    }

    /**
     * @dataProvider index_dataProvider
     */
    public function testParseSQLColumn(string $sql, MySQLIndex $expected): void
    {
        $tableSQL = "CREATE TABLE Test ($sql)";
        $parseArray = (new SQLParser)->parse($tableSQL);
        $indexArray = $parseArray['Test']['indexes'][0];
        $actual = MySQLIndex::fromSqlParserArray($indexArray);
        $this->assertEquals($expected, $actual);
        $this->assertEquals($sql, $expected->getCreateTableSqlString());
    }
}
