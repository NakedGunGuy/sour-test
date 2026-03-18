<?php

declare(strict_types=1);

namespace Sauerkraut\Validation\Rules;

use Sauerkraut\Validation\Rule;

readonly class Confirmed implements Rule
{
    public function validate(string $field, mixed $value, array $data): ?string
    {
        $confirmationField = $field . '_confirmation';

        if (!isset($data[$confirmationField]) || $value !== $data[$confirmationField]) {
            return "{$field} confirmation does not match.";
        }

        return null;
    }
}
