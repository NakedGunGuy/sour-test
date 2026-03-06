<?php

declare(strict_types=1);

namespace Sauerkraut\Database\Schema;

use Sauerkraut\Database\Connection;

class Inspector
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /** @return string[] */
    public function tableNames(): array
    {
        return match ($this->db->driver()) {
            'sqlite' => $this->sqliteTableNames(),
            'mysql' => $this->mysqlTableNames(),
            'pgsql' => $this->pgsqlTableNames(),
            default => throw new \RuntimeException("Unsupported driver: {$this->db->driver()}"),
        };
    }

    public function table(string $name): Table
    {
        return match ($this->db->driver()) {
            'sqlite' => $this->sqliteTable($name),
            'mysql' => $this->mysqlTable($name),
            'pgsql' => $this->pgsqlTable($name),
            default => throw new \RuntimeException("Unsupported driver: {$this->db->driver()}"),
        };
    }

    /** @return Table[] */
    public function allTables(): array
    {
        return array_map(fn (string $name) => $this->table($name), $this->tableNames());
    }

    // --- SQLite ---

    private function sqliteTableNames(): array
    {
        $rows = $this->db->queryAll(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        );
        return array_column($rows, 'name');
    }

    private function sqliteTable(string $name): Table
    {
        $columns = [];
        $rows = $this->db->queryAll("PRAGMA table_info('{$name}')");

        foreach ($rows as $row) {
            $columns[] = new Column(
                name: $row['name'],
                type: $this->normalizeType($row['type']),
                notNull: (bool) $row['notnull'],
                default: $row['dflt_value'],
                primaryKey: (bool) $row['pk'],
            );
        }

        $foreignKeys = [];
        $fkRows = $this->db->queryAll("PRAGMA foreign_key_list('{$name}')");

        foreach ($fkRows as $row) {
            $foreignKeys[] = new ForeignKey(
                column: $row['from'],
                referencesTable: $row['table'],
                referencesColumn: $row['to'],
            );
        }

        return new Table($name, $columns, $foreignKeys);
    }

    // --- MySQL ---

    private function mysqlTableNames(): array
    {
        $rows = $this->db->queryAll('SHOW TABLES');
        return array_map(fn ($row) => array_values($row)[0], $rows);
    }

    private function mysqlTable(string $name): Table
    {
        $columns = [];
        $rows = $this->db->queryAll("SHOW COLUMNS FROM `{$name}`");

        foreach ($rows as $row) {
            $columns[] = new Column(
                name: $row['Field'],
                type: $this->normalizeType($row['Type']),
                notNull: $row['Null'] === 'NO',
                default: $row['Default'],
                primaryKey: $row['Key'] === 'PRI',
            );
        }

        $foreignKeys = [];
        $fkRows = $this->db->queryAll(
            "SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$name]
        );

        foreach ($fkRows as $row) {
            $foreignKeys[] = new ForeignKey(
                column: $row['COLUMN_NAME'],
                referencesTable: $row['REFERENCED_TABLE_NAME'],
                referencesColumn: $row['REFERENCED_COLUMN_NAME'],
            );
        }

        return new Table($name, $columns, $foreignKeys);
    }

    // --- PostgreSQL ---

    private function pgsqlTableNames(): array
    {
        $rows = $this->db->queryAll(
            "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename"
        );
        return array_column($rows, 'tablename');
    }

    private function pgsqlTable(string $name): Table
    {
        $columns = [];
        $rows = $this->db->queryAll(
            "SELECT column_name, data_type, is_nullable, column_default
             FROM information_schema.columns
             WHERE table_schema = 'public' AND table_name = ?
             ORDER BY ordinal_position",
            [$name]
        );

        // Get primary key columns
        $pkRows = $this->db->queryAll(
            "SELECT a.attname
             FROM pg_index i
             JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
             WHERE i.indrelid = ?::regclass AND i.indisprimary",
            [$name]
        );
        $pkColumns = array_column($pkRows, 'attname');

        foreach ($rows as $row) {
            $columns[] = new Column(
                name: $row['column_name'],
                type: $this->normalizeType($row['data_type']),
                notNull: $row['is_nullable'] === 'NO',
                default: $row['column_default'],
                primaryKey: in_array($row['column_name'], $pkColumns),
            );
        }

        $foreignKeys = [];
        $fkRows = $this->db->queryAll(
            "SELECT
                kcu.column_name,
                ccu.table_name AS referenced_table,
                ccu.column_name AS referenced_column
             FROM information_schema.table_constraints tc
             JOIN information_schema.key_column_usage kcu
                ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
             JOIN information_schema.constraint_column_usage ccu
                ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema
             WHERE tc.constraint_type = 'FOREIGN KEY'
               AND tc.table_schema = 'public'
               AND tc.table_name = ?",
            [$name]
        );

        foreach ($fkRows as $row) {
            $foreignKeys[] = new ForeignKey(
                column: $row['column_name'],
                referencesTable: $row['referenced_table'],
                referencesColumn: $row['referenced_column'],
            );
        }

        return new Table($name, $columns, $foreignKeys);
    }

    // --- Type Normalization ---

    private function normalizeType(string $rawType): string
    {
        $type = strtolower(trim($rawType));
        // Strip size/precision: varchar(255) -> varchar
        $type = preg_replace('/\(.*\)/', '', $type);
        $type = trim($type);

        return match (true) {
            in_array($type, ['integer', 'int', 'smallint', 'mediumint', 'bigint', 'tinyint', 'int2', 'int4', 'int8', 'serial', 'bigserial']) => 'integer',
            in_array($type, ['real', 'float', 'double', 'decimal', 'numeric', 'double precision', 'money']) => 'real',
            in_array($type, ['boolean', 'bool']) => 'boolean',
            in_array($type, ['date']) => 'date',
            in_array($type, ['datetime', 'timestamp', 'timestamp without time zone', 'timestamp with time zone']) => 'datetime',
            in_array($type, ['blob', 'bytea']) => 'blob',
            default => 'text',
        };
    }
}
