<?php

declare(strict_types=1);

namespace Sauerkraut\Console\Commands;

use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Signature;
use Sauerkraut\Database\MigrationRepository;
use Sauerkraut\Database\Migrator;

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
        $migrator = $this->buildMigrator();
        $rolledBack = $migrator->rollback();

        if (empty($rolledBack)) {
            $output->info('Nothing to roll back.');
            return 0;
        }

        foreach ($rolledBack as $name) {
            $output->success("  Rolled back: {$name}");
        }

        return 0;
    }

    private function buildMigrator(): Migrator
    {
        $db = $this->app->db();

        return new Migrator(
            $db,
            new MigrationRepository($db),
            $this->app->basePath('database/migrations'),
        );
    }
}
