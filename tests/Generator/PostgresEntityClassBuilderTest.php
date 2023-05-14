<?php declare(strict_types=1);

namespace Composite\Sync\Tests\Generator;

use Composite\DB\ConnectionManager;
use Composite\Sync\Generator\EntityClassBuilder;
use Composite\Sync\Generator\EnumClassBuilder;
use Composite\Sync\Providers\PostgreSQL\PgSQLParser;

final class PostgresEntityClassBuilderTest extends \PHPUnit\Framework\TestCase
{
    const CONNECTION_NAME = 'postgres';
    const TABLE_NAME = 'test_table';

    /**
     * @dataProvider sql_dataProvider
     */
    public function test_generate(
        array $initQueries,
        string $entityClass,
        array $expectedEnums,
        string $expectedOutput
    ): void
    {
        $connection = ConnectionManager::getConnection(self::CONNECTION_NAME);
        $connection->executeQuery('DROP TABLE IF EXISTS ' . self::TABLE_NAME);
        foreach ($initQueries as $initQuery) {
            $connection->executeQuery($initQuery);
        }
        $sqlTable = (new PgSQLParser(
            connection: $connection,
            tableName: self::TABLE_NAME,
        ))->getSQLTable();

        $enums = [];
        foreach ($sqlTable->getEnumColumns() as $enumColumn) {
            $enums[$enumColumn->name] = EnumClassBuilder::getProposedEnumClass($entityClass, $enumColumn->name);
        }

        $classBuilder = new EntityClassBuilder(
            sqlTable: $sqlTable,
            connectionName: self::CONNECTION_NAME,
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
                [
                    "CREATE TABLE " . self::TABLE_NAME . " (
                        id SERIAL PRIMARY KEY,
                        first_name VARCHAR(255) NOT NULL,
                        last_name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) DEFAULT NULL,
                        phone_number VARCHAR(20) DEFAULT NULL,
                        age INTEGER DEFAULT NULL,
                        country VARCHAR(255) DEFAULT NULL,
                        signup_at TIMESTAMP(6) DEFAULT NULL,
                        account_balance DECIMAL(10,2) DEFAULT NULL,
                        is_active SMALLINT DEFAULT 0
                    );",
                ],
                'App\TestEntity',
                [],
                '<?php declare(strict_types=1);

namespace App;

use Composite\DB\Attributes\{Table, PrimaryKey};
use Composite\Sync\Attributes\{Column};
use Composite\Entity\AbstractEntity;

#[Table(connection: \'' . self::CONNECTION_NAME . '\', name: \'' . self::TABLE_NAME . '\')]
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
        public ?int $is_active = 0,
    ) {}
}
',
            ],
            [
                [
                    "DROP TYPE IF EXISTS Gender;",
                    "CREATE TYPE Gender AS ENUM ('male', 'female', 'other');",
                    "CREATE TABLE " . self::TABLE_NAME . " (
                        student_id INTEGER NOT NULL,
                        course_id INTEGER NOT NULL,
                        first_name VARCHAR(255) NOT NULL,
                        last_name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) DEFAULT NULL,
                        phone_number VARCHAR(20) DEFAULT NULL,
                        age INTEGER DEFAULT NULL,
                        country VARCHAR(255) DEFAULT NULL,
                        enrollment_date DATE DEFAULT NULL,
                        final_grade DECIMAL(5,2) DEFAULT NULL,
                        gender Gender DEFAULT 'other',
                        preferences JSON DEFAULT NULL,
                        last_update TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (student_id, course_id)
                    );",
                    "CREATE INDEX idx_enrollment_date ON " . self::TABLE_NAME . " (enrollment_date);",
                ],
                'App\Test\TestEntity',
                [
                    'gender' => 'App\Test\Enums\Gender',
                ],
                '<?php declare(strict_types=1);

namespace App\Test;

use Composite\DB\Attributes\{Table, PrimaryKey};
use Composite\Sync\Attributes\{Column, Index};
use Composite\Entity\AbstractEntity;
use App\Test\Enums\Gender;

#[Table(connection: \'' . self::CONNECTION_NAME . '\', name: \'' . self::TABLE_NAME . '\')]
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