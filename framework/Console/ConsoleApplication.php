<?php

declare(strict_types=1);

namespace Sauerkraut\Console;

use Sauerkraut\App;
use Sauerkraut\Console\Commands\HelpCommand;
use Sauerkraut\Console\Commands\PublishCommand;
use Sauerkraut\Console\Commands\EnvDecryptCommand;
use Sauerkraut\Console\Commands\EnvEncryptCommand;
use Sauerkraut\Console\Commands\EnvKeyCommand;
use Sauerkraut\Console\Commands\MakeMigrationCommand;
use Sauerkraut\Console\Commands\MigrateCommand;
use Sauerkraut\Console\Commands\MigrateRollbackCommand;
use Sauerkraut\Console\Commands\MigrateStatusCommand;
use Sauerkraut\Console\Commands\RoutesListCommand;
use Sauerkraut\Console\Commands\ScheduleListCommand;
use Sauerkraut\Console\Commands\TestCommand;
use Sauerkraut\Console\Commands\ScheduleRunCommand;

class ConsoleApplication
{
    /** @var array<string, Command> */
    private array $commands = [];

    public function __construct(private App $app)
    {
        $this->registerBuiltInCommands();
        $this->discoverAppCommands();
        $this->discoverPackageCommands();
    }

    public function run(array $argv): int
    {
        $commandName = $argv[1] ?? 'help';
        $command = $this->commands[$commandName] ?? null;

        if (!$command) {
            $output = new Output();
            $output->error("Unknown command: {$commandName}");
            $output->line("Run 'php sauerkraut help' for available commands.");
            return 1;
        }

        $signature = $command->signature();
        $input = new Input($signature, $argv);
        $output = new Output();

        return $command->handle($input, $output);
    }

    public function register(Command $command): void
    {
        $command->setApp($this->app);
        $this->commands[$command->signature()->name] = $command;
    }

    private function registerBuiltInCommands(): void
    {
        $help = new HelpCommand();
        $scheduleRun = new ScheduleRunCommand();
        $scheduleList = new ScheduleListCommand();

        $this->register($help);
        $this->register(new PublishCommand());
        $this->register($scheduleRun);
        $this->register($scheduleList);
        $this->register(new MigrateCommand());
        $this->register(new MigrateRollbackCommand());
        $this->register(new MigrateStatusCommand());
        $this->register(new MakeMigrationCommand());
        $this->register(new EnvKeyCommand());
        $this->register(new EnvEncryptCommand());
        $this->register(new EnvDecryptCommand());
        $this->register(new RoutesListCommand());
        $this->register(new TestCommand());

        $help->setCommands($this->commands);
        $scheduleRun->setCommands($this->commands);
        $scheduleList->setCommands($this->commands);
    }

    private function discoverAppCommands(): void
    {
        $commandsDir = $this->app->basePath('app/Commands');

        if (!is_dir($commandsDir)) {
            return;
        }

        foreach (glob("{$commandsDir}/*.php") as $file) {
            $className = 'App\\Commands\\' . pathinfo($file, PATHINFO_FILENAME);
            $this->registerIfValid($className);
        }

        $this->refreshCommandLists();
    }

    private function discoverPackageCommands(): void
    {
        foreach ($this->app->packages() as $name => $config) {
            $commands = $config['commands'] ?? [];

            foreach ($commands as $className) {
                $this->registerIfValid($className);
            }
        }

        $this->refreshCommandLists();
    }

    private function registerIfValid(string $className): void
    {
        if (!class_exists($className)) {
            return;
        }

        if (!is_subclass_of($className, Command::class)) {
            return;
        }

        $this->register(new $className());
    }

    private function refreshCommandLists(): void
    {
        foreach ($this->commands as $command) {
            if ($command instanceof HelpCommand
                || $command instanceof ScheduleRunCommand
                || $command instanceof ScheduleListCommand
            ) {
                $command->setCommands($this->commands);
            }
        }
    }
}
