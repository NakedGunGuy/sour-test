<?php

declare(strict_types=1);

namespace Sauerkraut\Database;

class Migrator
{
    public function __construct(
        private Connection $db,
        private MigrationRepository $repository,
        private string $migrationsPath,
    ) {}

    /** @return string[] Names of migrations that were run */
    public function migrate(): array
    {
        $this->repository->ensureTable();
        $pending = $this->pendingMigrations();

        if (empty($pending)) {
            return [];
        }

        $batch = $this->repository->nextBatchNumber();
        $ran = [];

        foreach ($pending as $name) {
            $migration = $this->resolve($name);
            $this->db->transaction(fn () => $migration->up());
            $this->repository->log($name, $batch);
            $ran[] = $name;
        }

        return $ran;
    }

    /** @return string[] Names of migrations that were rolled back */
    public function rollback(): array
    {
        $this->repository->ensureTable();
        $batch = $this->repository->lastBatchNumber();

        if ($batch === 0) {
            return [];
        }

        $migrations = $this->repository->migrationsForBatch($batch);
        $rolledBack = [];

        foreach ($migrations as $name) {
            $migration = $this->resolve($name);
            $this->db->transaction(fn () => $migration->down());
            $this->repository->delete($name);
            $rolledBack[] = $name;
        }

        return $rolledBack;
    }

    /** @return array<int, array{name: string, ran: bool}> */
    public function status(): array
    {
        $this->repository->ensureTable();
        $ran = array_flip($this->repository->ranMigrations());
        $statuses = [];

        foreach ($this->discoveredMigrations() as $name) {
            $statuses[] = [
                'name' => $name,
                'ran' => isset($ran[$name]),
            ];
        }

        return $statuses;
    }

    /** @return string[] */
    private function pendingMigrations(): array
    {
        $ran = array_flip($this->repository->ranMigrations());

        return array_values(array_filter(
            $this->discoveredMigrations(),
            fn (string $name) => !isset($ran[$name]),
        ));
    }

    /** @return string[] */
    private function discoveredMigrations(): array
    {
        $files = glob($this->migrationsPath . '/*.php');

        if ($files === false) {
            return [];
        }

        sort($files);

        return array_map(
            fn (string $file) => pathinfo($file, PATHINFO_FILENAME),
            $files,
        );
    }

    private function resolve(string $migration): Migration
    {
        $file = $this->migrationsPath . '/' . $migration . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException("Migration file not found: {$file}");
        }

        require_once $file;

        $className = $this->classNameFromFileName($migration);

        if (!class_exists($className)) {
            throw new \RuntimeException("Migration class '{$className}' not found in {$file}");
        }

        return new $className($this->db);
    }

    private function classNameFromFileName(string $migration): string
    {
        // Strip timestamp prefix: "2026_03_17_143022_create_posts_table" -> "create_posts_table"
        $description = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $migration);

        return str_replace(' ', '', ucwords(str_replace('_', ' ', $description)));
    }
}
