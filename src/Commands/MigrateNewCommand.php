<?php declare(strict_types=1);

namespace Composite\Sync\Commands;

use Composite\Sync\Migration\MigrationClassBuilder;
use Composite\Sync\Helpers\ClassHelper;
use Composite\Sync\Helpers\CommandHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(name: 'composite:migrate-new')]
class MigrateNewCommand extends Command
{
    use CommandHelper;

    protected function configure(): void
    {
        $this
            ->addArgument(name: 'connection', mode: InputArgument::OPTIONAL, description: 'Connection name')
            ->addArgument(name: 'description', mode: InputArgument::OPTIONAL, description: 'Execute migration without asking');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->storeInputOutput($input, $output);

        if (!$description = $this->input->getArgument('description')) {
            $description = $this->ask(new Question('Please provide short description: '));
        }
        $description = ClassHelper::normalizeString($description);
        if (!$description) {
            return $this->showError('Description is too short or not valid');
        }
        $connectionName = $this->getOrAskConnectionName();
        $classBuilder = new MigrationClassBuilder(
            connectionName: $connectionName,
            summaryParts: explode('_', $description),
            upQueries: ['/* Write your up query here */'],
            downQueries: ['/* Write your down query here */'],
        );
        $fileName = $classBuilder->save();
        return $this->showSuccess("New migration `$fileName` was successfully generated");
    }
}