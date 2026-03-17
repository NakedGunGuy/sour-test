<?php

declare(strict_types=1);

namespace Sauerkraut\Console\Commands;

use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Signature;

class HelpCommand extends Command
{
    /** @var Command[] */
    private array $commands = [];

    public function signature(): Signature
    {
        return new Signature(
            name: 'help',
            description: 'Show available commands',
        );
    }

    public function handle(Input $input, Output $output): int
    {
        $output->newLine();
        $output->info('Sauerkraut CLI');
        $output->info('==============');
        $output->newLine();
        $output->line('Usage: php sauerkraut <command> [arguments] [options]');
        $output->newLine();
        $output->line('Available commands:');

        foreach ($this->commands as $command) {
            $sig = $command->signature();
            $output->line('  ' . str_pad($sig->name, 20) . $sig->description);
        }

        $output->newLine();

        return 0;
    }

    /** @param Command[] $commands */
    public function setCommands(array $commands): void
    {
        $this->commands = $commands;
    }
}
