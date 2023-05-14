<?php declare(strict_types=1);

namespace Composite\Sync\Tests\Providers\MySQL;

use Composite\Sync\Providers\MySQL\MySQLColumn;
use Composite\Sync\Providers\MySQL\MySQLColumnType;
use iamcal\SQLParser;

final class MySQLColumnTest extends \PHPUnit\Framework\TestCase
{
    public function sqlColumn_DataProvider(): array
    {
        return [
            [
                "`bar2` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL",
                new MySQLColumn(
                    name: 'bar2',
                    type: MySQLColumnType::VARCHAR,
                    size: 255,
                    isNullable: false,
                    collation: 'utf8mb4_unicode_ci'
                )
            ],
            [
                "`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT",
                new MySQLColumn(
                    name: 'id',
                    type: MySQLColumnType::INT,
                    size: 11,
                    isNullable: false,
                    isAutoincrement: true,
                    unsigned: true
                )
            ],
            [
                "`email` VARCHAR(255) NOT NULL",
                new MySQLColumn(
                    name: 'email',
                    type: MySQLColumnType::VARCHAR,
                    size: 255,
                    isNullable: false
                )
            ],
            [
                "`age` INT(3) UNSIGNED NOT NULL",
                new MySQLColumn(
                    name: 'age',
                    type: MySQLColumnType::INT,
                    size: 3,
                    isNullable: false,
                    unsigned: true
                )
            ],
            [
                "`price` DECIMAL(10,2) NOT NULL",
                new MySQLColumn(
                    name: 'price',
                    type: MySQLColumnType::DECIMAL,
                    precision: 10,
                    scale: 2,
                    isNullable: false
                )
            ],
            [
                "`created_at` DATETIME NOT NULL",
                new MySQLColumn(
                    name: 'created_at',
                    type: MySQLColumnType::DATETIME,
                    isNullable: false
                )
            ],
            [
                "`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
                new MySQLColumn(
                    name: 'updated_at',
                    type: MySQLColumnType::TIMESTAMP,
                    isNullable: false,
                    hasDefaultValue: true,
                    defaultValue: 'CURRENT_TIMESTAMP',
                    onUpdate: 'CURRENT_TIMESTAMP'
                )
            ],
            [
                "`deleted` TINYINT(1) NOT NULL DEFAULT 0",
                new MySQLColumn(
                    name: 'deleted',
                    type: MySQLColumnType::TINYINT,
                    size: 1,
                    isNullable: false,
                    hasDefaultValue: true,
                    defaultValue: 0
                )
            ],
            [
                "`name` VARCHAR(100) NULL DEFAULT NULL",
                new MySQLColumn(
                    name: 'name',
                    type: MySQLColumnType::VARCHAR,
                    size: 100,
                    isNullable: true,
                    hasDefaultValue: true,
                    defaultValue: null
                )
            ],
            [
                "`status` ENUM('active','inactive') NOT NULL DEFAULT 'active'",
                new MySQLColumn(
                    name: 'status',
                    type: MySQLColumnType::ENUM,
                    isNullable: false,
                    hasDefaultValue: true,
                    defaultValue: 'active',
                    values: ['active', 'inactive']
                )
            ],
            [
                "`foo` CHAR(1) NOT NULL",
                new MySQLColumn(
                    name: 'foo',
                    type: MySQLColumnType::CHAR,
                    size: 1,
                    isNullable: false
                )
            ],
            [
                "`bar` TEXT COLLATE utf8_general_ci NOT NULL",
                new MySQLColumn(
                    name: 'bar',
                    type: MySQLColumnType::TEXT,
                    isNullable: false,
                    collation: 'utf8_general_ci'
                )
            ],
            [
                "`baz` LONGTEXT COLLATE utf8mb4_unicode_ci NULL",
                new MySQLColumn(
                    name: 'baz',
                    type: MySQLColumnType::LONGTEXT,
                    isNullable: true,
                    collation: 'utf8mb4_unicode_ci'
                )
            ],
            [
                "`float_col` FLOAT NOT NULL",
                new MySQLColumn(
                    name: 'float_col',
                    type: MySQLColumnType::FLOAT,
                    isNullable: false
                )
            ],
            [
                "`double_col` DOUBLE NOT NULL",
                new MySQLColumn(
                    name: 'double_col',
                    type: MySQLColumnType::DOUBLE,
                    isNullable: false
                )
            ],
            [
                "`real_col` REAL NOT NULL",
                new MySQLColumn(
                    name: 'real_col',
                    type: MySQLColumnType::REAL,
                    isNullable: false
                )
            ],
            [
                "`bit_col` BIT(8) NOT NULL",
                new MySQLColumn(
                    name: 'bit_col',
                    type: MySQLColumnType::BIT,
                    size: 8,
                    isNullable: false
                )
            ],
            [
                "`binary_col` BINARY(16) NOT NULL",
                new MySQLColumn(
                    name: 'binary_col',
                    type: MySQLColumnType::BINARY,
                    size: 16,
                    isNullable: false
                )
            ],
            [
                "`varbinary_col` VARBINARY(64) NOT NULL",
                new MySQLColumn(
                    name: 'varbinary_col',
                    type: MySQLColumnType::VARBINARY,
                    size: 64,
                    isNullable: false
                )
            ],
            [
                "`tinyblob_col` TINYBLOB NOT NULL",
                new MySQLColumn(
                    name: 'tinyblob_col',
                    type: MySQLColumnType::TINYBLOB,
                    isNullable: false
                )
            ],
            [
                "`mediumblob_col` MEDIUMBLOB NOT NULL",
                new MySQLColumn(
                    name: 'mediumblob_col',
                    type: MySQLColumnType::MEDIUMBLOB,
                    isNullable: false
                )
            ],
            [
                "`blob_col` BLOB NOT NULL",
                new MySQLColumn(
                    name: 'blob_col',
                    type: MySQLColumnType::BLOB,
                    isNullable: false
                )
            ],
            [
                "`longblob_col` LONGBLOB NOT NULL",
                new MySQLColumn(
                    name: 'longblob_col',
                    type: MySQLColumnType::LONGBLOB,
                    isNullable: false
                )
            ],
            [
                "`year_col` YEAR(4) NOT NULL",
                new MySQLColumn(
                    name: 'year_col',
                    type: MySQLColumnType::YEAR,
                    size: 4,
                    isNullable: false
                )
            ],
            [
                "`point_col` POINT NOT NULL",
                new MySQLColumn(
                    name: 'point_col',
                    type: MySQLColumnType::POINT,
                    isNullable: false
                )
            ],
            [
                "`linestring_col` LINESTRING NOT NULL",
                new MySQLColumn(
                    name: 'linestring_col',
                    type: MySQLColumnType::LINESTRING,
                    isNullable: false
                )
            ],
            [
                "`polygon_col` POLYGON NOT NULL",
                new MySQLColumn(
                    name: 'polygon_col',
                    type: MySQLColumnType::POLYGON,
                    isNullable: false
                )
            ],
            [
                "`geometry_col` GEOMETRY NOT NULL",
                new MySQLColumn(
                    name: 'geometry_col',
                    type: MySQLColumnType::GEOMETRY,
                    isNullable: false
                )
            ],
            [
                "`multilinestring_col` MULTILINESTRING NOT NULL",
                new MySQLColumn(
                    name: 'multilinestring_col',
                    type: MySQLColumnType::MULTILINESTRING,
                    isNullable: false
                )
            ],
            [
                "`multipoint_col` MULTIPOINT NOT NULL",
                new MySQLColumn(
                    name: 'multipoint_col',
                    type: MySQLColumnType::MULTIPOINT,
                    isNullable: false
                )
            ],
            [
                "`multipolygon_col` MULTIPOLYGON NOT NULL",
                new MySQLColumn(
                    name: 'multipolygon_col',
                    type: MySQLColumnType::MULTIPOLYGON,
                    isNullable: false
                )
            ],
            [
                "`geometrycollection_col` GEOMETRYCOLLECTION NOT NULL",
                new MySQLColumn(
                    name: 'geometrycollection_col',
                    type: MySQLColumnType::GEOMETRYCOLLECTION,
                    isNullable: false
                )
            ],
            [
                "`json_col` JSON NOT NULL",
                new MySQLColumn(
                    name: 'json_col',
                    type: MySQLColumnType::JSON,
                    isNullable: false
                )
            ],
            [
                "`tinyint_col` TINYINT(4) NOT NULL",
                new MySQLColumn(
                    name: 'tinyint_col',
                    type: MySQLColumnType::TINYINT,
                    size: 4,
                    isNullable: false,
                    unsigned: false
                )
            ],
            [
                "`smallint_col` SMALLINT(5) UNSIGNED NOT NULL",
                new MySQLColumn(
                    name: 'smallint_col',
                    type: MySQLColumnType::SMALLINT,
                    size: 5,
                    isNullable: false,
                    unsigned: true
                )
            ],
            [
                "`mediumint_col` MEDIUMINT(7) NOT NULL",
                new MySQLColumn(
                    name: 'mediumint_col',
                    type: MySQLColumnType::MEDIUMINT,
                    size: 7,
                    isNullable: false
                )
            ],
            [
                "`bigint_col` BIGINT(20) UNSIGNED NOT NULL",
                new MySQLColumn(
                    name: 'bigint_col',
                    type: MySQLColumnType::BIGINT,
                    size: 20,
                    isNullable: false,
                    unsigned: true
                )
            ],
            [
                "`serial_col` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT",
                new MySQLColumn(
                    name: 'serial_col',
                    type: MySQLColumnType::BIGINT,
                    size: 20,
                    isNullable: false,
                    isAutoincrement: true,
                    unsigned: true
                )
            ],
            [
                "`date_col` DATE NOT NULL",
                new MySQLColumn(
                    name: 'date_col',
                    type: MySQLColumnType::DATE,
                    isNullable: false
                )
            ],
            [
                "`time_col` TIME NOT NULL",
                new MySQLColumn(
                    name: 'time_col',
                    type: MySQLColumnType::TIME,
                    isNullable: false
                )
            ],
            [
                "`timestamp_col` TIMESTAMP NULL",
                new MySQLColumn(
                    name: 'timestamp_col',
                    type: MySQLColumnType::TIMESTAMP,
                    isNullable: true
                )
            ],
            [
                "`tinytext_col` TINYTEXT COLLATE utf8mb4_general_ci NULL",
                new MySQLColumn(
                    name: 'tinytext_col',
                    type: MySQLColumnType::TINYTEXT,
                    isNullable: true,
                    collation: 'utf8mb4_general_ci'
                )
            ],
            [
                "`mediumtext_col` MEDIUMTEXT COLLATE utf8_general_ci NULL",
                new MySQLColumn(
                    name: 'mediumtext_col',
                    type: MySQLColumnType::MEDIUMTEXT,
                    isNullable: true,
                    collation: 'utf8_general_ci'
                )
            ],
            [
                "`bool_col` BOOL NOT NULL DEFAULT 1",
                new MySQLColumn(
                    name: 'bool_col',
                    type: MySQLColumnType::BOOL,
                    isNullable: false,
                    hasDefaultValue: true,
                    defaultValue: 1
                )
            ],
            [
                "`dec_col` DEC(6,2) UNSIGNED NOT NULL",
                new MySQLColumn(
                    name: 'dec_col',
                    type: MySQLColumnType::DEC,
                    precision: 6,
                    scale: 2,
                    isNullable: false,
                    unsigned: true
                )
            ],
            [
                "`fixed_col` FIXED(8,3) NOT NULL",
                new MySQLColumn(
                    name: 'fixed_col',
                    type: MySQLColumnType::FIXED,
                    precision: 8,
                    scale: 3,
                    isNullable: false
                )
            ],
            [
                "`numeric_col` NUMERIC(10,4) UNSIGNED NOT NULL",
                new MySQLColumn(
                    name: 'numeric_col',
                    type: MySQLColumnType::NUMERIC,
                    precision: 10,
                    scale: 4,
                    isNullable: false,
                    unsigned: true
                )
            ],
            [
                "`float_col` FLOAT(7,3) UNSIGNED NOT NULL",
                new MySQLColumn(
                    name: 'float_col',
                    type: MySQLColumnType::FLOAT,
                    precision: 7,
                    scale: 3,
                    isNullable: false,
                    unsigned: true
                )
            ],
            [
                "`double_col` DOUBLE(8,2) UNSIGNED NOT NULL",
                new MySQLColumn(
                    name: 'double_col',
                    type: MySQLColumnType::DOUBLE,
                    precision: 8,
                    scale: 2,
                    isNullable: false,
                    unsigned: true
                )
            ],
            [
                "`real_col` REAL(9,3) NOT NULL",
                new MySQLColumn(
                    name: 'real_col',
                    type: MySQLColumnType::REAL,
                    precision: 9,
                    scale: 3,
                    isNullable: false
                )
            ],
            [
                "`time_col` TIME(3) NOT NULL",
                new MySQLColumn(
                    name: 'time_col',
                    type: MySQLColumnType::TIME,
                    isNullable: false,
                    fsp: 3
                )
            ],
            [
                "`datetime_col` DATETIME(6) NOT NULL",
                new MySQLColumn(
                    name: 'datetime_col',
                    type: MySQLColumnType::DATETIME,
                    isNullable: false,
                    fsp: 6
                )
            ],
            [
                "`timestamp_col` TIMESTAMP(3) NOT NULL",
                new MySQLColumn(
                    name: 'timestamp_col',
                    type: MySQLColumnType::TIMESTAMP,
                    isNullable: false,
                    fsp: 3
                )
            ],
            [
                "`year_col` YEAR NOT NULL",
                new MySQLColumn(
                    name: 'year_col',
                    type: MySQLColumnType::YEAR,
                    isNullable: false
                )
            ],
            [
                "`set_col` SET('apple','banana','cherry') NOT NULL",
                new MySQLColumn(
                    name: 'set_col',
                    type: MySQLColumnType::SET,
                    isNullable: false,
                    values: ['apple', 'banana', 'cherry']
                )
            ],
            [
                "`uuid` CHAR(36) NULL",
                new MySQLColumn(
                    name: 'uuid',
                    type: MySQLColumnType::CHAR,
                    size: 36,
                    isNullable: true
                )
            ],
            [
                "`description` MEDIUMTEXT NULL",
                new MySQLColumn(
                    name: 'description',
                    type: MySQLColumnType::MEDIUMTEXT,
                    isNullable: true
                )
            ],
            [
                "`rating` DECIMAL(3,2) UNSIGNED NULL DEFAULT NULL",
                new MySQLColumn(
                    name: 'rating',
                    type: MySQLColumnType::DECIMAL,
                    precision: 3,
                    scale: 2,
                    isNullable: true,
                    hasDefaultValue: true,
                    defaultValue: null,
                    unsigned: true
                )
            ],
            [
                "`views` BIGINT(20) UNSIGNED NULL DEFAULT 0",
                new MySQLColumn(
                    name: 'views',
                    type: MySQLColumnType::BIGINT,
                    size: 20,
                    isNullable: true,
                    hasDefaultValue: true,
                    defaultValue: 0,
                    unsigned: true
                )
            ],
            [
                "`published_at` DATE NULL",
                new MySQLColumn(
                    name: 'published_at',
                    type: MySQLColumnType::DATE,
                    isNullable: true
                )
            ],
            [
                "`start_time` TIME(1) NULL",
                new MySQLColumn(
                    name: 'start_time',
                    type: MySQLColumnType::TIME,
                    isNullable: true,
                    fsp: 1,
                )
            ],
            [
                "`end_time` TIME(4) NULL",
                new MySQLColumn(
                    name: 'end_time',
                    type: MySQLColumnType::TIME,
                    isNullable: true,
                    fsp: 4
                )
            ],
            [
                "`coordinates` POINT NULL",
                new MySQLColumn(
                    name: 'coordinates',
                    type: MySQLColumnType::POINT,
                    isNullable: true
                )
            ],
            [
                "`tags` SET('red','green','blue') NULL",
                new MySQLColumn(
                    name: 'tags',
                    type: MySQLColumnType::SET,
                    isNullable: true,
                    values: ['red', 'green', 'blue']
                )
            ],
            [
                "`metadata` JSON NULL",
                new MySQLColumn(
                    name: 'metadata',
                    type: MySQLColumnType::JSON,
                    isNullable: true
                )
            ],
        ];
    }

    /**
     * @dataProvider sqlColumn_DataProvider
     */
    public function test_parseSQLColumn(string $sql, MySQLColumn $expected): void
    {
        $tableSQL = "CREATE TABLE Test ($sql)";
        $parseArray = (new SQLParser)->parse($tableSQL);
        $columnArray = $parseArray['Test']['fields'][0];
        $actual = MySQLColumn::fromSqlParserArray($columnArray);
        $this->assertEquals($expected, $actual);
        $this->assertEquals($sql, $expected->getSqlString());
    }
}
