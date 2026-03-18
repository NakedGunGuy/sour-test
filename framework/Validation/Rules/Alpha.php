<?php

declare(strict_types=1);

namespace Sauerkraut\Validation\Rules;

use Sauerkraut\Validation\Rule;

readonly class Alpha implements Rule
{
    public function validate(string $field, mixed $value, array $data): ?string
    {
        if (!is_string($value) || !ctype_alpha($value)) {
            return "{$field} must only contain letters.";
        }

        return null;
    }
}
