<?php

declare(strict_types=1);

namespace Sauerkraut\Validation\Rules;

use Sauerkraut\Validation\Rule;

readonly class Min implements Rule
{
    public function __construct(private int|float $min) {}

    public function validate(string $field, mixed $value, array $data): ?string
    {
        if (is_numeric($value) && $value < $this->min) {
            return "{$field} must be at least {$this->min}.";
        }

        if (is_string($value) && mb_strlen($value) < $this->min) {
            return "{$field} must be at least {$this->min} characters.";
        }

        return null;
    }
}
