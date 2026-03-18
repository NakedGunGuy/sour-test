<?php

declare(strict_types=1);

namespace Sauerkraut\Validation;

readonly class ValidationResult
{
    /**
     * @param array<string, string[]> $errors
     * @param array<string, mixed> $validated
     */
    public function __construct(
        private array $errors,
        private array $validated,
    ) {}

    public function passed(): bool
    {
        return $this->errors === [];
    }

    public function failed(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string[]> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function error(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /** @return array<string, mixed> */
    public function validated(): array
    {
        return $this->validated;
    }
}
