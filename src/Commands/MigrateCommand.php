<?php declare(strict_types=1);

namespace Composite\Sync\Commands;

use Composer\Autoload\ClassLoader;
use Composite\DB\Attributes\Table;
use Composite\Sync\Attributes\SkipMigration;
use Composite\Sync\Helpers\ConsoleLogger;
use Composite\Sync\Migration\MigrationsManager;
use Composite\Sync\Helpers\CommandHelper;
use Composite\DB\TableConfig;
use Composite\Entity\AbstractEntity;
use Composite\Entity\Exceptions\EntityException;
use Laminas\File\ClassFileLocator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(name: 'composite:migrate')]
class MigrateCommand extends Command
{
    use CommandHelper;

    protected function configure(): void
    {
        $this
            ->addOption(name: 'connection', shortcut: 'c', mode: InputOption::VALUE_OPTIONAL, description: 'Connection name')
            ->addOption(name: 'entity', shortcut: 'e', mode: InputOption::VALUE_OPTIONAL, description: 'Entity class must be checked')
            ->addOption(name: 'run', shortcut: 'r', description: 'Run migrations without asking for confirmation')
            ->addOption(name: 'dry', shortcut: 'd', description: 'Dry run mode simulates SQL queries without executing them');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->storeInputOutput($input, $output);

        $logger = new ConsoleLogger($output);
        $manager = new MigrationsManager(
            logger: $logger,
            dryRun: $input->getOption('dry'),
        );
        $manager->scanMigrationsDirectory();

        if ($manager->getWaitingMigrations()) {
            return $this->executeMigration($manager);
        }
        if ($onlyEntity = $this->input->getOption('entity')) {
            if (!class_exists($onlyEntity)) {
                return $this->showError("Class `$onlyEntity` not exists");
            }
            $classes[] = $onlyEntity;
        } else {
            if (!$classes = $this->findAllEntities()) {
                return $this->showAlert("No entities found in your project");
            }
        }
        $nothingChanged = true;
        foreach ($classes as $class) {
            if (!$classBuilder = $manager->checkClass($class)) {
                continue;
            }
            $fileName = $classBuilder->save();
            $this->showSuccess("New migration `$fileName` was successfully generated");
            $nothingChanged = false;
        }
        if ($nothingChanged) {
            return $this->showSuccess('Nothing changed');
        }
        return Command::SUCCESS;
    }

    /**
     * @return class-string<AbstractEntity>[]
     * @throws \Exception
     */
    private function findAllEntities(): array
    {
        $result = [];
        $dirs = [];
        $onlyConnectionName = $this->input->getOption('connection');

        $entitiesDir = getenv('ENTITIES_DIR', true) ?: ($_ENV['ENTITIES_DIR'] ?? null);
        if ($entitiesDir) {
            if (!is_dir($entitiesDir)) {
                throw new \Exception('ENV variable `ENTITIES_DIR` contains incorrect dir path');
            }
            $dirs[] = $entitiesDir;
        } else {
            $loaders = ClassLoader::getRegisteredLoaders();
            foreach ($loaders as $loader) {
                foreach ($loader->getPrefixesPsr4() as $psr4dirs) {
                    foreach ($psr4dirs as $psr4dir) {
                        $realPath = realpath($psr4dir);
                        if (str_contains($realPath, 'vendor')) {
                            continue;
                        }
                        $dirs[] = $realPath;
                    }
                }
            }
            $dirs = array_unique($dirs);
        }

        foreach ($dirs as $dir) {
            $locator = new ClassFileLocator($dir);
            foreach ($locator as $file) {
                foreach ($file->getClasses() as $class) {
                    if (!is_subclass_of($class, AbstractEntity::class)) {
                        continue;
                    }
                    $schema = $class::schema();
                    if ($schema->getFirstAttributeByClass(SkipMigration::class)) {
                        continue;
                    }
                    if (!$schema->getFirstAttributeByClass(Table::class)) {
                        continue;
                    }
                    try {
                        $tableConfig = TableConfig::fromEntitySchema($schema);
                    } catch (EntityException) {
                        continue;
                    }
                    if ($onlyConnectionName && $tableConfig->connectionName !== $onlyConnectionName) {
                        continue;
                    }
                    $result[] = $class;
                }
            }
        }
        return $result;
    }

    private function executeMigration(MigrationsManager $manager): int
    {
        $migrations = $manager->getWaitingMigrations();
        $runNow = $this->input->getOption('run');
        if (!$runNow) {
            $this->showAlert('You have migrations pending execution:');
            foreach ($migrations as $i => $migrationName) {
                $this->showAlert(($i + 1) . ". $migrationName");
            }
            $runNow = $this->ask(new ConfirmationQuestion('Would you like to execute them? [y/n]: '));
        }
        if ($runNow === true) {
            foreach ($migrations as $migrationName) {
                $this->showAlert("Executing $migrationName");
                $manager->executeMigration($migrationName);
            }
            $this->showSuccess('Done');
        }
        return Command::SUCCESS;
    }
}