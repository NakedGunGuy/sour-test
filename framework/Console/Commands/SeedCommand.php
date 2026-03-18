<?php

declare(strict_types=1);

namespace Sauerkraut\Console\Commands;

use Sauerkraut\Console\Argument;
use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Signature;
use Sauerkraut\Database\SeederRunner;

class SeedCommand extends Command
{
    public function signature(): Signature
    {
        return new Signature(
            name: 'db:seed',
            description: 'Run database seeders',
            arguments: [
                new Argument('seeder', 'Specific seeder class name to run'),
            ],
        );
    }

    public function handle(Input $input, Output $output): int
    {
        $runner = new SeederRunner(
            $this->app->db(),
            $this->app->basePath('database/seeders'),
        );

        $specific = $input->argument('seeder');
        $ran = $runner->run($specific);

        if (empty($ran)) {
            $output->info('No seeders found.');
            return 0;
        }

        foreach ($ran as $name) {
            $output->success("  Seeded: {$name}");
        }

        return 0;
    }
}
