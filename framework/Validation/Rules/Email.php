<?php

declare(strict_types=1);

namespace Sauerkraut\Validation\Rules;

use Sauerkraut\Validation\Rule;

readonly class Email implements Rule
{
    public function validate(string $field, mixed $value, array $data): ?string
    {
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return "{$field} must be a valid email address.";
        }

        return null;
    }
}
