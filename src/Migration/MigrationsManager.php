<?php declare(strict_types=1);

namespace Composite\Sync\Migration;

use Composite\DB\ConnectionManager;
use Composite\Sync\Providers\AbstractComparator;
use Composite\Sync\Providers\MySQL\MySQLComparator;
use Composite\Sync\Providers\MySQL\MySQLTable;
use Composite\Sync\Providers\SQLTableFactory;
use Composite\DB\TableConfig;
use Composite\Entity\AbstractEntity;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Psr\Log\LoggerInterface;

class MigrationsManager
{
    /** @var array<string, MigrationVersionTable> */
    private array $versionTables = [];

    /** @var string[] */
    private array $migrationsOnDisc;

    public function __construct(
        private readonly ?LoggerInterface $logger = null,
        private readonly bool $dryRun = false,
    ) {}

    public function executeMigration(string $migrationName): void
    {
        $className = $this->getMigrationClass($migrationName);

        /** @var AbstractMigration $migration */
        $migration = new $className(
            name: $migrationName,
            logger: $this->logger,
            dryRun: $this->dryRun,
        );
        if ($this->dryRun) {
            $migration->up();
            return;
        }
        $connectionName = $migration::getConnectionName();
        $versionTable = $this->getVersionTable($connectionName);
        $versionTable->insertVersion($migration);
    }

    public function rollbackMigration(string $migrationName): void
    {
        $className = $this->getMigrationClass($migrationName);

        /** @var AbstractMigration $migration */
        $migration = new $className(
            name: $migrationName,
            logger: $this->logger,
            dryRun: $this->dryRun,
        );
        if ($this->dryRun) {
            $migration->down();
            return;
        }
        $connectionName = $migration::getConnectionName();
        $versionTable = $this->getVersionTable($connectionName);
        $versionTable->deleteVersion($migration);
    }

    /**
     * @return string[]
     */
    public function getWaitingMigrations(): array
    {
        $result = [];
        foreach ($this->migrationsOnDisc as $migrationName) {
            $migrationClass = $this->getMigrationClass($migrationName);
            $connectionName = $migrationClass::CONNECTION_NAME;

            if ($this->getVersionTable($connectionName)->checkMigrationExecuted($migrationName)) {
                continue;
            }
            $result[] = $migrationName;
        }
        return $result;
    }

    public static function getMigrationsDirectory(): string
    {
        $dir = (getenv('MIGRATIONS_DIR', true) ?: ($_ENV['MIGRATIONS_DIR'] ?? false)) ?: throw new \Exception('ENV variable `MIGRATIONS_DIR` is not set');
        if (!\str_ends_with($dir, DIRECTORY_SEPARATOR)) {
            $dir .= DIRECTORY_SEPARATOR;
        }
        if (!is_dir($dir)) {
            throw new \Exception('ENV variable `MIGRATIONS_DIR` contains incorrect dir path');
        }
        if (!is_writeable($dir)) {
            throw new \Exception("Directory `$dir` is not writable, please change permissions");
        }
        return $dir;
    }

    /**
     * @return class-string<AbstractMigration>
     */
    public static function getMigrationFullClassName(string $migrationName): string
    {
        $result = '\\' . $migrationName;
        $migrationNamespace = getenv('MIGRATIONS_NAMESPACE', true) ?: ($_ENV['MIGRATIONS_NAMESPACE'] ?? false);
        if ($migrationNamespace) {
            if (str_ends_with($migrationNamespace, '\\')) {
                $migrationNamespace = substr($migrationNamespace, 0,-1);
            }
            $result = $migrationNamespace . $result;
        }
        return $result;
    }

    /**
     * @param string[] $summaryParts
     */
    public static function buildMigrationName(string $connectionName, array $summaryParts): string
    {
        $result = 'Migration_' . date('ymdhis') . "_{$connectionName}";
        foreach ($summaryParts as $i => $part) {
            if (strlen($result) >= 200) {
                $partsLeft = count($summaryParts) - $i;
                $result .= "_and_{$partsLeft}_more";
                break;
            }
            $result .= '_' . $part;
        }
        return $result;
    }

    public function scanMigrationsDirectory(): void
    {
        $this->migrationsOnDisc = [];
        $dir = self::getMigrationsDirectory();
        foreach (glob($dir . 'Migration_*.php') as $file) {
            $pathParts = pathinfo($file);
            $migrationName = $pathParts['filename'];
            $this->migrationsOnDisc[] = $migrationName;
        }
    }

    public function checkClass(string $class): ?MigrationClassBuilder
    {
        $comparator = $this->getComparator($class);
        $upQueries = array_filter($comparator->getUpQueries());
        $downQueries = array_filter($comparator->getDownQueries());
        if (!$upQueries) {
            return null;
        }
        return new MigrationClassBuilder(
            connectionName: $comparator->connectionName,
            summaryParts: $comparator->getSummaryParts(),
            upQueries: $upQueries,
            downQueries: $downQueries,
        );
    }

    /**
     * @return string[]
     */
    public function getLastExecutedMigrations(string $connectionName, int $limit): array
    {
        $result = [];
        foreach ($this->getVersionTable($connectionName)->findAll() as $migrationVersion) {
            if (count($result) >= $limit) {
                break;
            }
            if (!\in_array($migrationVersion->version, $this->migrationsOnDisc)) {
                continue;
            }
            $result[] = $migrationVersion->version;
        }
        return $result;
    }

    private function getFilePath(string $migrationName): string
    {
        $filePath = self::getMigrationsDirectory() . DIRECTORY_SEPARATOR . $migrationName . '.php';
        if (!is_file($filePath)) {
            throw new \Exception("Migration file `$filePath` not found.");
        }
        return $filePath;
    }

    private function getMigrationClass(string $migrationName): string
    {
        $className = self::getMigrationFullClassName($migrationName);
        if (!class_exists($className)) {
            $migrationFilePath = $this->getFilePath($migrationName);
            require $migrationFilePath;
            if (!class_exists($className)) {
                throw new \Exception("Something went wrong, migration class `$className` not found in file `$migrationFilePath`.");
            }
        }
        return $className;
    }

    /**
     * @param class-string<AbstractEntity> $entityClass
     * @throws \Composite\DB\Exceptions\DbException
     * @throws \Doctrine\DBAL\Exception
     */
    private function getComparator(string $entityClass): AbstractComparator
    {
        $schema = $entityClass::schema();
        $tableConfig = TableConfig::fromEntitySchema($schema);
        $connection = ConnectionManager::getConnection($tableConfig->connectionName);

        $databaseTable = SQLTableFactory::parseFromDatabase($tableConfig->connectionName, $tableConfig->tableName);
        if ($connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            return new MySQLComparator(
                connectionName: $tableConfig->connectionName,
                entityTable: MySQLTable::fromEntitySchema($schema),
                databaseTable: $databaseTable,
            );
        }
        throw new \Exception($connection->getDatabasePlatform()::class . ' is not supported');
    }

    private function getVersionTable(string $connectionName): MigrationVersionTable
    {
        if (!isset($this->versionTables[$connectionName])) {
            $this->versionTables[$connectionName] = new MigrationVersionTable($connectionName);
        }
        return $this->versionTables[$connectionName];
    }
}