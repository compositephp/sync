<?php declare(strict_types=1);

namespace Composite\Sync\Tests\Generator;

use Composite\Sync\Generator\EntityClassBuilder;
use Composite\Sync\Generator\EnumClassBuilder;
use Composite\Sync\Providers\MySQL\MySQLParser;

final class MySQLEntityClassBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider sql_dataProvider
     */
    public function test_generate(
        string $tableName,
        string $sqlQuery,
        string $entityClass,
        array $expectedEnums,
        string $expectedOutput
    ): void
    {
        $sqlTable = (new MySQLParser(
            tableName: $tableName,
            sql: $sqlQuery,
        ))->getSQLTable();

        $enums = [];
        foreach ($sqlTable->getEnumColumns() as $enumColumn) {
            $enums[$enumColumn->name] = EnumClassBuilder::getProposedEnumClass($entityClass, $enumColumn->name);
        }

        $classBuilder = new EntityClassBuilder(
            sqlTable: $sqlTable,
            connectionName: 'mysql',
            entityClass: $entityClass,
            enums: $enums,
        );
        $actualOutput = $classBuilder->getFileContent();
        $this->assertSame($expectedEnums, $enums);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function sql_dataProvider(): array
    {
        return [
            [
                'test_table1',
                "CREATE TABLE `test_table1` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `first_name` varchar(255) NOT NULL,
                    `last_name` varchar(255) NOT NULL,
                    `email` varchar(255) DEFAULT NULL,
                    `phone_number` varchar(20) DEFAULT NULL,
                    `age` int(11) DEFAULT NULL,
                    `country` varchar(255) DEFAULT NULL,
                    `signup_at` TIMESTAMP(6) DEFAULT NULL,
                    `account_balance` decimal(10,2) DEFAULT NULL,
                    `is_active` tinyint(1) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
                'App\TestEntity',
                [],
                '<?php declare(strict_types=1);

namespace App;

use Composite\DB\Attributes\{Table, PrimaryKey};
use Composite\Sync\Attributes\{Column};
use Composite\Entity\AbstractEntity;

#[Table(connection: \'mysql\', name: \'test_table1\')]
class TestEntity extends AbstractEntity
{
    #[PrimaryKey(autoIncrement: true)]
    public readonly int $id;

    public function __construct(
        public string $first_name,
        public string $last_name,
        public ?string $email = null,
        #[Column(size: 20)]
        public ?string $phone_number = null,
        public ?int $age = null,
        public ?string $country = null,
        public ?\DateTimeImmutable $signup_at = null,
        #[Column(precision: 10, scale: 2)]
        public ?float $account_balance = null,
        public bool $is_active = false,
    ) {}
}
',
            ],
            [
                'test_table2',
                "CREATE TABLE `test_table2` (
                    `student_id` int(11) NOT NULL,
                    `course_id` int(11) NOT NULL,
                    `first_name` varchar(255) NOT NULL,
                    `last_name` varchar(255) NOT NULL,
                    `email` varchar(255) DEFAULT NULL,
                    `phone_number` varchar(20) DEFAULT NULL,
                    `age` int(11) DEFAULT NULL,
                    `country` varchar(255) DEFAULT NULL,
                    `enrollment_date` date DEFAULT NULL,
                    `final_grade` decimal(5,2) DEFAULT NULL,
                    `gender` ENUM('male', 'female', 'other') NULL DEFAULT 'other',
                    `preferences` json DEFAULT NULL,
                    `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`student_id`, `course_id`),
                    KEY `idx_enrollment_date` (`enrollment_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ",
                'App\TestEntity',
                [
                    'gender' => 'App\Enums\Gender',
                ],
                '<?php declare(strict_types=1);

namespace App;

use Composite\DB\Attributes\{Table, PrimaryKey};
use Composite\Sync\Attributes\{Column, Index};
use Composite\Entity\AbstractEntity;
use App\Enums\Gender;

#[Table(connection: \'mysql\', name: \'test_table2\')]
#[Index(columns: [\'enrollment_date\'], name: \'idx_enrollment_date\')]
class TestEntity extends AbstractEntity
{
    public function __construct(
        #[PrimaryKey]
        public readonly int $student_id,
        #[PrimaryKey]
        public readonly int $course_id,
        public string $first_name,
        public string $last_name,
        public ?string $email = null,
        #[Column(size: 20)]
        public ?string $phone_number = null,
        public ?int $age = null,
        public ?string $country = null,
        public ?string $enrollment_date = null,
        #[Column(precision: 5, scale: 2)]
        public ?float $final_grade = null,
        public ?Gender $gender = Gender::other,
        public ?array $preferences = null,
        public \DateTimeImmutable $last_update = new \DateTimeImmutable(),
    ) {}
}
',
            ],
        ];
    }
}