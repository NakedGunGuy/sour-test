<?php

declare(strict_types=1);

namespace Sauerkraut\Validation\Rules;

use Sauerkraut\Validation\Rule;

readonly class IsString implements Rule
{
    public function validate(string $field, mixed $value, array $data): ?string
    {
        if (!is_string($value)) {
            return "{$field} must be a string.";
        }

        return null;
    }
}
