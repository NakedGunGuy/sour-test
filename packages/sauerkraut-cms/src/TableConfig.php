<?php

declare(strict_types=1);

namespace Sauerkraut\CMS;

use Sauerkraut\Database\Schema\LabelResolver;
use Sauerkraut\Database\Schema\Table;

class TableConfig
{
    private const TIMESTAMP_COLUMNS = ['created_at', 'updated_at', 'deleted_at'];

    private Table $table;
    private array $config;
    private LabelResolver $labelResolver;

    public function __construct(Table $table, array $config, LabelResolver $labelResolver)
    {
        $this->table = $table;
        $this->config = $config;
        $this->labelResolver = $labelResolver;
    }

    public function displayName(): string
    {
        if (isset($this->config['label'])) {
            return $this->config['label'];
        }

        return ucfirst(str_replace('_', ' ', $this->table->name));
    }

    public function labelColumn(): string
    {
        if (isset($this->config['label_column'])) {
            return $this->config['label_column'];
        }

        return $this->labelResolver->labelColumn($this->table);
    }

    public function hiddenColumns(): array
    {
        return $this->config['hidden_columns'] ?? [];
    }

    public function readonlyColumns(): array
    {
        return $this->config['readonly_columns'] ?? [];
    }

    /** @return string[] */
    public function listColumns(): array
    {
        $hidden = $this->hiddenColumns();
        $columns = [];

        foreach ($this->table->columns as $col) {
            if (!in_array($col->name, $hidden)) {
                $columns[] = $col->name;
            }
        }

        return $columns;
    }

    /** @return Field[] */
    public function formFields(): array
    {
        $hidden = $this->hiddenColumns();
        $readonly = $this->readonlyColumns();
        $columnOverrides = $this->config['columns'] ?? [];
        $fields = [];

        foreach ($this->table->columns as $col) {
            // Skip primary key, timestamps, hidden columns
            if ($col->primaryKey) {
                continue;
            }
            if (in_array($col->name, self::TIMESTAMP_COLUMNS)) {
                continue;
            }
            if (in_array($col->name, $hidden)) {
                continue;
            }

            $override = $columnOverrides[$col->name] ?? [];
            $fk = $this->table->foreignKeyFor($col->name);

            $label = $override['label'] ?? ucfirst(str_replace('_', ' ', $col->name));
            $type = $this->resolveFieldType($col, $override, $fk);
            $options = $override['options'] ?? [];
            $isReadonly = in_array($col->name, $readonly) || ($override['readonly'] ?? false);

            $fields[] = new Field(
                name: $col->name,
                label: $label,
                type: $type,
                required: $col->notNull && $col->default === null,
                default: $col->default,
                options: $options,
                readonly: $isReadonly,
            );
        }

        return $fields;
    }

    private function resolveFieldType(
        \Sauerkraut\Database\Schema\Column $col,
        array $override,
        ?\Sauerkraut\Database\Schema\ForeignKey $fk
    ): string {
        // Config override takes priority
        if (isset($override['type'])) {
            return $override['type'];
        }

        // FK columns become select
        if ($fk) {
            return 'select';
        }

        return match ($col->type) {
            'integer' => 'number',
            'real' => 'number',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'text',
            'blob' => 'textarea',
            default => 'text',
        };
    }

    public function table(): Table
    {
        return $this->table;
    }
}
