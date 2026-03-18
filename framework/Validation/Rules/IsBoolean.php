<?php

declare(strict_types=1);

namespace Sauerkraut\Validation\Rules;

use Sauerkraut\Validation\Rule;

readonly class IsBoolean implements Rule
{
    private const array ACCEPTED = [true, false, 1, 0, '1', '0'];

    public function validate(string $field, mixed $value, array $data): ?string
    {
        if (!in_array($value, self::ACCEPTED, strict: true)) {
            return "{$field} must be true or false.";
        }

        return null;
    }
}
