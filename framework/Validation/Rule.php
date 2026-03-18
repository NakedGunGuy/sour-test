<?php

declare(strict_types=1);

namespace Sauerkraut\Validation;

interface Rule
{
    /**
     * @return string|null Null if valid, error message if invalid
     */
    public function validate(string $field, mixed $value, array $data): ?string;
}
