<?php

declare(strict_types=1);

namespace Sauerkraut\Database;

class MigrationRepository
{
    public function __construct(private Connection $db) {}

    public function ensureTable(): void
    {
        $sql = match ($this->db->driver()) {
            'sqlite' => 'CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INTEGER NOT NULL
            )',
            'mysql' => 'CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL
            )',
            'pgsql' => 'CREATE TABLE IF NOT EXISTS migrations (
                id SERIAL PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INTEGER NOT NULL
            )',
        };

        $this->db->execute($sql);
    }

    /** @return string[] */
    public function ranMigrations(): array
    {
        $rows = $this->db->queryAll('SELECT migration FROM migrations ORDER BY batch, id');

        return array_column($rows, 'migration');
    }

    public function lastBatchNumber(): int
    {
        $row = $this->db->queryOne('SELECT MAX(batch) as batch FROM migrations');

        return (int) ($row['batch'] ?? 0);
    }

    public function nextBatchNumber(): int
    {
        return $this->lastBatchNumber() + 1;
    }

    /** @return string[] Migrations in reverse order for rollback */
    public function migrationsForBatch(int $batch): array
    {
        $rows = $this->db->queryAll(
            'SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC',
            [$batch],
        );

        return array_column($rows, 'migration');
    }

    public function log(string $migration, int $batch): void
    {
        $this->db->execute(
            'INSERT INTO migrations (migration, batch) VALUES (?, ?)',
            [$migration, $batch],
        );
    }

    public function delete(string $migration): void
    {
        $this->db->execute(
            'DELETE FROM migrations WHERE migration = ?',
            [$migration],
        );
    }
}
