<?php declare(strict_types=1);

include_once dirname(__DIR__) . '/vendor/autoload.php';

putenv('CONNECTIONS_CONFIG_FILE=' . __DIR__ . '/config.php');
putenv('MIGRATIONS_DIR=' . __DIR__ . '/runtime/migrations');
putenv('MIGRATIONS_NAMESPACE=Composite\Sync\Tests\runtime\migrations');