<?php

declare(strict_types=1);

namespace Sauerkraut\Console\Commands;

use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Signature;

class MigrateStatusCommand extends Command
{
    public function signature(): Signature
    {
        return new Signature(
            name: 'migrate:status',
            description: 'Show the status of each migration',
        );
    }

    public function handle(Input $input, Output $output): int
    {
        $statuses = $this->app->migrator()->status();

        if (empty($statuses)) {
            $output->info('No migrations found.');
            return 0;
        }

        $output->newLine();

        $rows = array_map(fn (array $status) => [
            $status['name'],
            $status['ran'] ? 'Ran' : 'Pending',
        ], $statuses);

        $output->table(['Migration', 'Status'], $rows);
        $output->newLine();

        return 0;
    }
}
