<?php

declare(strict_types=1);

namespace Sauerkraut\Validation\Rules;

use Sauerkraut\Validation\Rule;

readonly class Nullable implements Rule
{
    public function validate(string $field, mixed $value, array $data): ?string
    {
        return null;
    }
}
