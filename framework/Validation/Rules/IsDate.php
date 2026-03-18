<?php

declare(strict_types=1);

namespace Sauerkraut\Validation\Rules;

use Sauerkraut\Validation\Rule;

readonly class IsDate implements Rule
{
    public function validate(string $field, mixed $value, array $data): ?string
    {
        if (!is_string($value) || strtotime($value) === false) {
            return "{$field} must be a valid date.";
        }

        return null;
    }
}
