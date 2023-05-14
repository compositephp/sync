<?php declare(strict_types=1);

namespace Composite\Sync\Commands;

use Composite\Sync\Generator\CachedTableClassBuilder;
use Composite\Sync\Generator\TableClassBuilder;
use Composite\Sync\Helpers\CommandHelper;
use Composite\DB\TableConfig;
use Composite\Entity\AbstractEntity;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(name: 'composite:generate-table')]
class GenerateTableCommand extends Command
{
    use CommandHelper;

    protected function configure(): void
    {
        $this
            ->addArgument('entity', InputArgument::REQUIRED, 'Entity full class name')
            ->addArgument('table', InputArgument::OPTIONAL, 'Table full class name')
            ->addOption('cached', 'c', InputOption::VALUE_NONE, 'Generate cached version')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing table class file');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->storeInputOutput($input, $output);
        /** @var class-string<AbstractEntity> $entityClass */
        $entityClass = $input->getArgument('entity');
        $reflection = new \ReflectionClass($entityClass);

        if (!$reflection->isSubclassOf(AbstractEntity::class)) {
            return $this->showError("Class `$entityClass` must be subclass of " . AbstractEntity::class);
        }
        $schema = $entityClass::schema();
        $tableConfig = TableConfig::fromEntitySchema($schema);
        $tableName = $tableConfig->tableName;

        if (!$tableClass = $this->getTableClass($reflection, $tableName)) {
            return Command::FAILURE;
        }

        if ($input->getOption('cached')) {
            $template = new CachedTableClassBuilder(
                tableClass: $tableClass,
                entityClass: $entityClass,
            );
        } else {
            $template = new TableClassBuilder(
                tableClass: $tableClass,
                entityClass: $entityClass,
            );
        }
        $fileContent = $template->getFileContent();
        return $this->saveClassToFile($tableClass, $fileContent) ? Command::SUCCESS : Command::FAILURE;
    }

    private function getTableClass(\ReflectionClass $entityReflection, string $tableName): ?string
    {
        if (!$tableClass = $this->input->getArgument('table')) {
            $proposedClass = preg_replace('/\w+$/', 'Tables', $entityReflection->getNamespaceName()) . "\\{$tableName}Table";
            $tableClass = $this->ask(new Question("Enter table full class name [skip to use $proposedClass]: "));
            if (!$tableClass) {
                $tableClass = $proposedClass;
            }
        }
        if (str_starts_with($tableClass, '\\')) {
            $tableClass = substr($tableClass, 1);
        }
        if (!preg_match('/^(.+)\\\(\w+)$/', $tableClass)) {
            $this->showError("Table class `$tableClass` is incorrect");
            return null;
        }
        return $tableClass;
    }
}
