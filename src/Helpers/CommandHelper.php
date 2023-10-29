<?php declare(strict_types=1);

namespace Composite\Sync\Helpers;

use Composite\DB\ConnectionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

trait CommandHelper
{
    protected InputInterface $input;
    protected OutputInterface $output;

    protected function storeInputOutput(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->output = $output;
    }

    private function showSuccess(string $text): int
    {
        $this->output->writeln("<fg=green>$text</fg=green>");
        return Command::SUCCESS;
    }

    private function showAlert(string $text): int
    {
        $this->output->writeln("<fg=yellow>$text</fg=yellow>");
        return Command::SUCCESS;
    }

    private function showError(string $text): int
    {
        $this->output->writeln("<fg=red>$text</fg=red>");
        return Command::INVALID;
    }

    protected function ask(Question $question): mixed
    {
        return (new QuestionHelper())->ask($this->input, $this->output, $question);
    }

    private function saveClassToFile(string $class, string $content): bool
    {
        $filePath = ClassHelper::getClassFilePath($class);
        $fileState = 'new';
        if (file_exists($filePath)) {
            $fileState = 'overwrite';
            if (!$this->input->getOption('force')
                && !$this->ask(new ConfirmationQuestion("File `$filePath` is already exists, do you want to overwrite it?[y/n]: "))) {
                return true;
            }
        }
        if (file_put_contents($filePath, $content)) {
            $this->showSuccess("File `$filePath` was successfully generated ($fileState)");
            return true;
        } else {
            $this->showError("Something went wrong can `$filePath` was successfully generated ($fileState)");
            return false;
        }
    }

    protected function getOrAskConnectionName(): string
    {
        $reflectionMethod = new \ReflectionMethod(ConnectionManager::class, 'loadConfigs');
        $configs = $reflectionMethod->invoke(null);
        $availableConnections = array_keys($configs);
        if (!$availableConnections) {
            throw new \Exception(ConnectionManager::class . ' is not configured, please read the documentation');
        }

        $connectionName = $this->input->getArgument('connection');
        if (!$connectionName && count($availableConnections) === 1) {
            $connectionName = $availableConnections[0];
        }
        if (!$connectionName || !in_array($connectionName, $availableConnections)) {
            if ($connectionName) {
                $this->showAlert("Connection `$connectionName` not found in config");
            }
            $connectionName = $this->ask(new ChoiceQuestion('Choose connection: ', $availableConnections));
        }
        return $connectionName;
    }

    protected function clear(): void
    {
        system('clear');
    }
}