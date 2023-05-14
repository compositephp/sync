<?php declare(strict_types=1);

namespace Composite\Sync\Providers;

use Composite\Sync\Providers\MySQL\MySQLParser;
use Composite\Sync\Providers\PostgreSQL\PgSQLParser;
use Composite\Sync\Providers\SQLite\SQLiteParser;
use Composite\DB\ConnectionManager;
use Doctrine\DBAL;

class SQLTableFactory
{
    static public function parseFromDatabase(string $connectionName, string $tableName): ?AbstractSQLTable
    {
        $connection = ConnectionManager::getConnection($connectionName);

        if ($connection->getDatabasePlatform() instanceof DBAL\Platforms\AbstractMySQLPlatform) {
            $parser = MySQLParser::fromConnection($connection, $tableName);
            return $parser->getSQLTable();
        } elseif ($connection->getDatabasePlatform() instanceof DBAL\Platforms\PostgreSQLPlatform) {
            $parser = new PgSQLParser($connection, $tableName);
            return $parser->getSQLTable();
        } elseif ($connection->getDatabasePlatform() instanceof DBAL\Platforms\SqlitePlatform) {
            $parser = new SQLiteParser($connection, $tableName);
            return $parser->getSQLTable();
        }
        throw new \Exception($connection->getDatabasePlatform()::class . ' is not supported');
    }
}