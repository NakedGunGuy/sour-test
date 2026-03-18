<?php

declare(strict_types=1);

namespace Sauerkraut\Validation\Rules;

use Sauerkraut\Validation\Rule;

readonly class Required implements Rule
{
    public function validate(string $field, mixed $value, array $data): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return "{$field} is required.";
        }

        return null;
    }
}
