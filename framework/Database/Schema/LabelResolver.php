<?php

declare(strict_types=1);

namespace Sauerkraut\Database\Schema;

use Sauerkraut\Database\Connection;

class LabelResolver
{
    private const LABEL_CANDIDATES = ['name', 'title', 'label', 'display_name'];

    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function labelColumn(Table $table): string
    {
        // Priority 1: well-known label column names
        foreach (self::LABEL_CANDIDATES as $candidate) {
            if ($table->column($candidate)) {
                return $candidate;
            }
        }

        // Priority 2: first non-PK text column
        foreach ($table->columns as $col) {
            if (!$col->primaryKey && $col->type === 'text') {
                return $col->name;
            }
        }

        // Fallback: PK
        $pk = $table->primaryKeyColumn();
        return $pk ? $pk->name : $table->columns[0]->name;
    }

    public function labelFor(Table $table, mixed $id): string
    {
        $pk = $table->primaryKeyColumn();
        if (!$pk) {
            return (string) $id;
        }

        $labelCol = $this->labelColumn($table);
        $row = $this->db->queryOne(
            "SELECT \"{$labelCol}\" FROM \"{$table->name}\" WHERE \"{$pk->name}\" = ?",
            [$id]
        );

        return $row ? (string) $row[$labelCol] : (string) $id;
    }
}
