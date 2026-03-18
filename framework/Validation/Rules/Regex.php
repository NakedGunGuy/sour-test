<?php

declare(strict_types=1);

namespace Sauerkraut\Validation\Rules;

use Sauerkraut\Validation\Rule;

readonly class Regex implements Rule
{
    public function __construct(private string $pattern) {}

    public function validate(string $field, mixed $value, array $data): ?string
    {
        if (!is_string($value) || !preg_match($this->pattern, $value)) {
            return "{$field} format is invalid.";
        }

        return null;
    }
}
