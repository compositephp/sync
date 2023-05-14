<?php declare(strict_types=1);

namespace Composite\Sync\Migration;

use Composite\DB\ConnectionManager;
use Composite\Sync\Helpers\ClassHelper;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

abstract class AbstractMigration
{
    public const CONNECTION_NAME = null;

    public function __construct(
        public readonly string $name,
        private readonly ?LoggerInterface $logger = null,
        private readonly bool $dryRun = false,
    ) {}

    public function getName(): string
    {
        return ClassHelper::extractShortName($this::class);
    }

    protected function query(string $sql, array $params = []): void
    {
        if ($sql) {
            $sql = str_replace("\t\t", "", $sql);
            $this->logger?->info(trim($sql, "\n"));
            if (!$this->dryRun) {
                $this->getConnection()->executeQuery($sql, $params);
            }
        } else {
            $this->logger?->info('Executing empty SQL, skipping...');
        }
    }

    protected function getConnection(): Connection
    {
        return ConnectionManager::getConnection(static::getConnectionName());
    }

    public static function getConnectionName(): string
    {
        return static::CONNECTION_NAME ?? throw new \Exception(static::class . '::CONNECTION_NAME is not set');
    }

    abstract public function up(): void;

    abstract public function down(): void;
}