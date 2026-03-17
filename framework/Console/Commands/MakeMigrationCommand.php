<?php

declare(strict_types=1);

namespace Sauerkraut\Console\Commands;

use Sauerkraut\Console\Argument;
use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Signature;

class MakeMigrationCommand extends Command
{
    public function signature(): Signature
    {
        return new Signature(
            name: 'make:migration',
            description: 'Create a new migration file',
            arguments: [
                new Argument('name', 'Migration name in snake_case (e.g. create_posts_table)', required: true),
            ],
        );
    }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->argument('name');

        if (!$name) {
            $output->error('Please provide a migration name.');
            return 1;
        }

        $migrationsDir = $this->app->basePath('database/migrations');
        $this->ensureDirectory($migrationsDir);

        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}";
        $className = $this->className($name);
        $path = "{$migrationsDir}/{$fileName}.php";

        file_put_contents($path, $this->stubContent($className));

        $output->success("Created: database/migrations/{$fileName}.php");

        return 0;
    }

    private function className(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    private function stubContent(string $className): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        use Sauerkraut\Database\Migration;

        class {$className} extends Migration
        {
            public function up(): void
            {
                \$this->db->execute('');
            }

            public function down(): void
            {
                \$this->db->execute('');
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
