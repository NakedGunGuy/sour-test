<?php

declare(strict_types=1);

namespace Sauerkraut\Console\Commands;

use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Schedulable;
use Sauerkraut\Console\Signature;

class ScheduleListCommand extends Command
{
    /** @var Command[] */
    private array $commands = [];

    public function signature(): Signature
    {
        return new Signature(
            name: 'schedule:list',
            description: 'List all scheduled commands',
        );
    }

    public function handle(Input $input, Output $output): int
    {
        $scheduled = $this->collectScheduledCommands();

        if (empty($scheduled)) {
            $output->warn('No scheduled commands registered.');
            return 0;
        }

        $output->newLine();
        $output->info('Scheduled Commands');
        $output->newLine();

        $headers = ['Command', 'Expression', 'Description', 'Next Run', 'Overlap'];
        $rows = [];

        foreach ($scheduled as [$command, $schedule]) {
            $sig = $command->signature();
            $rows[] = [
                $sig->name,
                $schedule->expression,
                $schedule->description ?? $sig->description,
                $schedule->nextRunDate()->format('Y-m-d H:i'),
                $schedule->preventOverlapping ? 'prevented' : 'allowed',
            ];
        }

        $output->table($headers, $rows);
        $output->newLine();

        return 0;
    }

    /** @return array<int, array{0: Command, 1: \Sauerkraut\Console\Schedule}> */
    private function collectScheduledCommands(): array
    {
        $scheduled = [];

        foreach ($this->commands as $command) {
            if (!$command instanceof Schedulable) {
                continue;
            }

            $scheduled[] = [$command, $command->schedule()];
        }

        return $scheduled;
    }

    /** @param Command[] $commands */
    public function setCommands(array $commands): void
    {
        $this->commands = $commands;
    }
}
