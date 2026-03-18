<?php

declare(strict_types=1);

namespace Sauerkraut\Validation\Rules;

use Sauerkraut\Database\Connection;
use Sauerkraut\Validation\Rule;

readonly class Unique implements Rule
{
    public function __construct(
        private Connection $db,
        private string $table,
        private string $column,
        private mixed $except = null,
    ) {}

    public function validate(string $field, mixed $value, array $data): ?string
    {
        $sql = "SELECT COUNT(*) as count FROM \"{$this->table}\" WHERE \"{$this->column}\" = ?";
        $params = [$value];

        if ($this->except !== null) {
            $sql .= ' AND "id" != ?';
            $params[] = $this->except;
        }

        $row = $this->db->queryOne($sql, $params);

        if ($row && (int) $row['count'] > 0) {
            return "{$field} has already been taken.";
        }

        return null;
    }
}
