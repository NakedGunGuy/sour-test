<?php

declare(strict_types=1);

namespace Sauerkraut\Validation\Rules;

use Sauerkraut\Validation\Rule;

readonly class Max implements Rule
{
    public function __construct(private int|float $max) {}

    public function validate(string $field, mixed $value, array $data): ?string
    {
        if (is_numeric($value) && $value > $this->max) {
            return "{$field} must be at most {$this->max}.";
        }

        if (is_string($value) && mb_strlen($value) > $this->max) {
            return "{$field} must be at most {$this->max} characters.";
        }

        return null;
    }
}
