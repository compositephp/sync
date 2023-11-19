<?php declare(strict_types=1);

namespace Composite\Sync\Migration;

use Composite\DB\TableConfig;
use Doctrine\DBAL\Platforms;

class MigrationVersionTable extends \Composite\DB\AbstractTable
{
    public const TABLE_NAME = '__migrations';
    public function __construct(
        private readonly string $connectionName,
    )
    {
        parent::__construct();
        $this->init();
    }

    protected function getConfig(): TableConfig
    {
        return new TableConfig(
            connectionName: $this->connectionName,
            tableName: '__migrations',
            entityClass:MigrationVersion::class,
            primaryKeys: ['version'],
        );
    }

    public function checkMigrationExecuted(string $version): bool
    {
        return (bool)$this->_findOne(['version' => $version]);
    }

    /**
     * @return MigrationVersion[]
     */
    public function findAll(): array
    {
        return $this->_findAll(orderBy: ['executed_at' => 'DESC']);
    }

    public function insertVersion(AbstractMigration $migration): void
    {
        $entity = new MigrationVersion($migration->getName());

        $nativeConnection = $this->getConnection()->getNativeConnection();
        if ($nativeConnection instanceof \PDO) {
            try {
                $nativeConnection->beginTransaction();

                $this->save($entity);
                $migration->up();

                if ($nativeConnection->inTransaction()) {
                    $nativeConnection->commit();
                }
            } catch (\Throwable $e) {
                if ($nativeConnection->inTransaction()) {
                    $nativeConnection->rollBack();
                }
                throw $e;
            }
        } else {
            $this->getConnection()->transactional(function () use ($migration, $entity) {
                $this->save($entity);
                $migration->up();
            });
        }
    }

    public function deleteVersion(AbstractMigration $migration): void
    {
        $entity = $this->_findOne(['version' => $migration->getName()]);
        $nativeConnection = $this->getConnection()->getNativeConnection();
        if ($nativeConnection instanceof \PDO) {
            try {
                $nativeConnection->beginTransaction();

                if ($entity) {
                    $this->delete($entity);
                }
                $migration->down();

                if ($nativeConnection->inTransaction()) {
                    $nativeConnection->commit();
                }
            } catch (\Throwable $e) {
                if ($nativeConnection->inTransaction()) {
                    $nativeConnection->rollBack();
                }
                throw $e;
            }
        } else {
            $this->getConnection()->transactional(function () use ($migration, $entity) {
                if ($entity) {
                    $this->delete($entity);
                }
                $migration->down();
            });
        }
    }

    private function init(): void
    {
        $connection = $this->getConnection();
        $platform = $connection->getDatabasePlatform();
        if ($platform instanceof Platforms\AbstractMySQLPlatform || $platform instanceof Platforms\PostgreSQLPlatform) {
            $connection->executeQuery(<<<SQL
                CREATE TABLE IF NOT EXISTS `{$this->getTableName()}`
                (
                    `version`     VARCHAR(255) NOT NULL PRIMARY KEY,
                    `executed_at` TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                );
            SQL);
        } elseif ($platform instanceof Platforms\SqlitePlatform) {
            $connection->executeQuery(<<<SQL
                CREATE TABLE IF NOT EXISTS `{$this->getTableName()}`
                (
                    `version`      TEXT NOT NULL PRIMARY KEY,
                    `executed_at`  DATETIME NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%f', 'now'))
                );
            SQL);
        } else {
            throw new \Exception(sprintf('Platform `%s` is not yet supported.', $platform::class));
        }
    }
}