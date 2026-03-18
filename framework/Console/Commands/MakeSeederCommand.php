<?php

declare(strict_types=1);

namespace Sauerkraut\Console\Commands;

use Sauerkraut\Console\Argument;
use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Signature;

class MakeSeederCommand extends Command
{
    public function signature(): Signature
    {
        return new Signature(
            name: 'make:seeder',
            description: 'Create a new seeder class',
            arguments: [
                new Argument('name', 'Seeder class name (e.g. CategoriesSeeder)', required: true),
            ],
        );
    }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->argument('name');

        if (!$name) {
            $output->error('Please provide a seeder name.');
            return 1;
        }

        $seedersDir = $this->app->basePath('database/seeders');
        $this->ensureDirectory($seedersDir);

        $path = "{$seedersDir}/{$name}.php";

        if (file_exists($path)) {
            $output->error("Seeder already exists: {$name}.php");
            return 1;
        }

        file_put_contents($path, $this->stubContent($name));

        $output->success("Created: database/seeders/{$name}.php");

        return 0;
    }

    private function stubContent(string $className): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        use Sauerkraut\Database\Seeder;

        class {$className} extends Seeder
        {
            public function run(): void
            {
                //
            }
        }

        PHP;
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}
