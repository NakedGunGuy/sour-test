<?php

declare(strict_types=1);

namespace Sauerkraut\Database;

class SeederRunner
{
    public function __construct(
        private Connection $db,
        private string $seedersPath,
    ) {}

    /** @return string[] Names of seeders that were run */
    public function run(?string $specific = null): array
    {
        if ($specific !== null) {
            return $this->runSingle($specific);
        }

        return $this->runAll();
    }

    /** @return string[] */
    private function runAll(): array
    {
        $files = glob($this->seedersPath . '/*.php');

        if ($files === false || empty($files)) {
            return [];
        }

        sort($files);
        $ran = [];

        foreach ($files as $file) {
            $className = $this->resolveClassName($file);
            $this->runSeeder($className, $file);
            $ran[] = $className;
        }

        return $ran;
    }

    /** @return string[] */
    private function runSingle(string $name): array
    {
        $file = $this->seedersPath . '/' . $name . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException("Seeder file not found: {$name}.php");
        }

        $className = $this->resolveClassName($file);
        $this->runSeeder($className, $file);

        return [$className];
    }

    private function runSeeder(string $className, string $file): void
    {
        require_once $file;

        if (!class_exists($className)) {
            throw new \RuntimeException("Seeder class '{$className}' not found in {$file}");
        }

        $seeder = new $className($this->db);
        $seeder->run();
    }

    private function resolveClassName(string $file): string
    {
        return pathinfo($file, PATHINFO_FILENAME);
    }
}
