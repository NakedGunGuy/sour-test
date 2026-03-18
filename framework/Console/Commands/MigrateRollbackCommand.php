<?php

declare(strict_types=1);

namespace Sauerkraut\Console\Commands;

use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Signature;

class MigrateRollbackCommand extends Command
{
    public function signature(): Signature
    {
        return new Signature(
            name: 'migrate:rollback',
            description: 'Roll back the last batch of migrations',
        );
    }

    public function handle(Input $input, Output $output): int
    {
        $rolledBack = $this->app->migrator()->rollback();

        if (empty($rolledBack)) {
            $output->info('Nothing to roll back.');
            return 0;
        }

        foreach ($rolledBack as $name) {
            $output->success("  Rolled back: {$name}");
        }

        return 0;
    }
}
