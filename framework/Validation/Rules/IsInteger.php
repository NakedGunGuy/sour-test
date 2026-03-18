<?php

declare(strict_types=1);

namespace Sauerkraut\Validation\Rules;

use Sauerkraut\Validation\Rule;

readonly class IsInteger implements Rule
{
    public function validate(string $field, mixed $value, array $data): ?string
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return "{$field} must be an integer.";
        }

        return null;
    }
}
