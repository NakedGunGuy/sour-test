<?php

declare(strict_types=1);

namespace Sauerkraut\Validation\Rules;

use Sauerkraut\Validation\Rule;

readonly class In implements Rule
{
    /** @param array<int, mixed> $allowed */
    public function __construct(private array $allowed) {}

    public function validate(string $field, mixed $value, array $data): ?string
    {
        if (!in_array($value, $this->allowed, strict: true)) {
            $list = implode(', ', $this->allowed);
            return "{$field} must be one of: {$list}.";
        }

        return null;
    }
}
