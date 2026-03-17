<?php

declare(strict_types=1);

namespace Sauerkraut\Console\Commands;

use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Signature;
use Sauerkraut\Database\MigrationRepository;
use Sauerkraut\Database\Migrator;

class MigrateCommand extends Command
{
    public function signature(): Signature
    {
        return new Signature(
            name: 'migrate',
            description: 'Run pending database migrations',
        );
    }

    public function handle(Input $input, Output $output): int
    {
        $migrator = $this->buildMigrator();
        $ran = $migrator->migrate();

        if (empty($ran)) {
            $output->info('Nothing to migrate.');
            return 0;
        }

        foreach ($ran as $name) {
            $output->success("  Migrated: {$name}");
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
