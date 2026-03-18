<?php

declare(strict_types=1);

namespace Sauerkraut\Validation\Rules;

use Sauerkraut\Validation\Rule;

readonly class Between implements Rule
{
    public function __construct(
        private int|float $min,
        private int|float $max,
    ) {}

    public function validate(string $field, mixed $value, array $data): ?string
    {
        if (!is_numeric($value)) {
            return "{$field} must be a number.";
        }

        if ($value < $this->min || $value > $this->max) {
            return "{$field} must be between {$this->min} and {$this->max}.";
        }

        return null;
    }
}
