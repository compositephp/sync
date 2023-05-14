<?php declare(strict_types=1);

namespace Composite\Sync\Helpers;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleLogger implements LoggerInterface
{
    public function __construct(
        private readonly OutputInterface $output,
    ) {}

    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->output->writeln("<fg=red>$message</fg=red>");
    }

    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->output->writeln("<fg=red>$message</fg=red>");
    }

    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->output->writeln("<fg=red>$message</fg=red>");
    }

    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->output->writeln("<fg=red>$message</fg=red>");
    }

    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->output->writeln("<fg=yellow>$message</fg=yellow>");
    }

    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->output->writeln("<fg=yellow>$message</fg=yellow>");
    }

    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->output->writeln("<fg=blue>$message</fg=blue>");
    }

    public function debug(\Stringable|string $message, array $context = []): void
    {
        $this->output->writeln("<fg=blue>$message</fg=blue>");
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->output->writeln("<fg=blue>$message</fg=blue>");
    }
}