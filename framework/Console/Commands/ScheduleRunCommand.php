<?php

declare(strict_types=1);

namespace Sauerkraut\Console\Commands;

use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Schedulable;
use Sauerkraut\Console\ScheduleLock;
use Sauerkraut\Console\Signature;

class ScheduleRunCommand extends Command
{
    /** @var Command[] */
    private array $commands = [];

    public function signature(): Signature
    {
        return new Signature(
            name: 'schedule:run',
            description: 'Run all due scheduled commands',
        );
    }

    public function handle(Input $input, Output $output): int
    {
        $lock = new ScheduleLock($this->app->basePath('storage/schedule'));
        $ranCount = 0;

        foreach ($this->commands as $command) {
            if (!$command instanceof Schedulable) {
                continue;
            }

            $schedule = $command->schedule();

            if (!$schedule->isDue()) {
                continue;
            }

            $name = $command->signature()->name;

            if ($schedule->preventOverlapping && !$lock->acquire($name, $schedule->lockExpiresAfter)) {
                $output->warn("  Skipped: {$name} (still running)");
                continue;
            }

            $output->info("  Running: {$name}");
            $commandInput = new Input($command->signature(), ['sauerkraut', $name]);
            $exitCode = $command->handle($commandInput, $output);

            if ($schedule->preventOverlapping) {
                $lock->release($name);
            }

            if ($exitCode === 0) {
                $output->success("  Finished: {$name}");
            } else {
                $output->error("  Failed: {$name} (exit code {$exitCode})");
            }

            $ranCount++;
        }

        if ($ranCount === 0) {
            $output->line('  No scheduled commands are due.');
        }

        return 0;
    }

    /** @param Command[] $commands */
    public function setCommands(array $commands): void
    {
        $this->commands = $commands;
    }
}
