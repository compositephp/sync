<?php declare(strict_types=1);

namespace Composite\Sync\Commands;

use Composite\Sync\Helpers\ConsoleLogger;
use Composite\Sync\Migration\MigrationsManager;
use Composite\Sync\Helpers\CommandHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(name: 'composite:migrate-down')]
class MigrateDownCommand extends Command
{
    use CommandHelper;

    protected function configure(): void
    {
        $this
            ->addArgument(name: 'connection', mode: InputArgument::OPTIONAL, description: 'Connection name')
            ->addArgument(name: 'limit', description: 'Number of migrations should be rolled back from current', default: 1)
            ->addOption(name: 'dry', shortcut: 'd', description: 'Dry run mode, no real SQL queries will be executed');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->storeInputOutput($input, $output);
        $connectionName = $this->getOrAskConnectionName();
        $limit = (int)$this->input->getArgument('limit');
        if (!$limit || $limit < 0) {
            return $this->showError('Argument `limit` must be greater than 0');
        }

        $logger = new ConsoleLogger($output);
        $manager = new MigrationsManager(
            logger: $logger,
            dryRun: $input->getOption('dry'),
        );
        $manager->scanMigrationsDirectory();
        $migrations = $manager->getLastExecutedMigrations($connectionName, $limit);
        if (!$migrations) {
            return $this->showAlert('No migrations identified for rollback');
        }
        $this->showAlert('Upcoming migrations to be rolled back:');
        foreach ($migrations as $i => $migrationName) {
            $this->showAlert(($i + 1) . ". $migrationName");
        }
        $execute = $this->ask(new ConfirmationQuestion('Would you like to proceed? [y/n]: '));
        if ($execute) {
            foreach ($migrations as $migrationName) {
                $this->showAlert("Rolling back $migrationName");
                $manager->rollbackMigration($migrationName);
            }
            $this->showSuccess('Done');
        }
        return Command::SUCCESS;
    }
}