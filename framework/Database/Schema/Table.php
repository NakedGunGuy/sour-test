<?php

declare(strict_types=1);

namespace Sauerkraut\Database\Schema;

class Table
{
    /**
     * @param Column[] $columns
     * @param ForeignKey[] $foreignKeys
     */
    public function __construct(
        public readonly string $name,
        public readonly array $columns,
        public readonly array $foreignKeys = [],
    ) {}

    public function primaryKeyColumn(): ?Column
    {
        foreach ($this->columns as $col) {
            if ($col->primaryKey) {
                return $col;
            }
        }
        return null;
    }

    public function column(string $name): ?Column
    {
        foreach ($this->columns as $col) {
            if ($col->name === $name) {
                return $col;
            }
        }
        return null;
    }

    public function foreignKeyFor(string $column): ?ForeignKey
    {
        foreach ($this->foreignKeys as $fk) {
            if ($fk->column === $column) {
                return $fk;
            }
        }
        return null;
    }
}
