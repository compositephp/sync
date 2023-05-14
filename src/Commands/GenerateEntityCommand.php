<?php declare(strict_types=1);

namespace Composite\Sync\Commands;

use Composite\Sync\Generator\EntityClassBuilder;
use Composite\Sync\Generator\EnumClassBuilder;
use Composite\Sync\Providers\AbstractSQLColumn;
use Composite\Sync\Providers\SQLTableFactory;
use Composite\Sync\Helpers\CommandHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

#[AsCommand(name: 'composite:generate-entity')]
class GenerateEntityCommand extends Command
{
    use CommandHelper;

    protected function configure(): void
    {
        $this
            ->addArgument('connection', InputArgument::REQUIRED, 'Connection name')
            ->addArgument('table', InputArgument::REQUIRED, 'Table name')
            ->addArgument('entity', InputArgument::OPTIONAL, 'Entity full class name')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing file');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->storeInputOutput($input, $output);
        $connectionName = $input->getArgument('connection');
        $tableName = $input->getArgument('table');

        if (!$entityClass = $input->getArgument('entity')) {
            $entityClass = $this->ask(new Question('Enter entity full class name: '));
        }
        $entityClass = str_replace('\\\\', '\\', $entityClass);

        $SQLTable = SQLTableFactory::parseFromDatabase($connectionName, $tableName);

        $enums = [];
        foreach ($SQLTable->getEnumColumns() as $enumColumn) {
            if ($enumClass = $this->generateEnum($entityClass, $enumColumn)) {
                $enums[$enumColumn->name] = $enumClass;
            }
        }
        $entityBuilder = new EntityClassBuilder($SQLTable, $connectionName, $entityClass, $enums);
        $content = $entityBuilder->getFileContent();

        $this->saveClassToFile($entityClass, $content);
        return Command::SUCCESS;
    }

    private function generateEnum(string $entityClass, AbstractSQLColumn $column): ?string
    {
        $name = $column->name;
        $values = $column->values;
        if (!$values) {
            return null;
        }
        $this->showAlert("Found enum `$name` with values [" . implode(', ', $values) . "]");
        if (!$this->ask(new ConfirmationQuestion('Do you want to generate Enum class?[y/n]: '))) {
            return null;
        }
        $proposedClass = EnumClassBuilder::getProposedEnumClass($entityClass, $name);
        $enumClass = $this->ask(new Question("Enter enum full class name [skip to use $proposedClass]: "));
        if (!$enumClass) {
            $enumClass = $proposedClass;
        }
        $enumClassBuilder = new EnumClassBuilder($enumClass, $values);

        $content = $enumClassBuilder->getFileContent();
        if (!$this->saveClassToFile($enumClass, $content)) {
            return null;
        }
        return $enumClass;
    }
}