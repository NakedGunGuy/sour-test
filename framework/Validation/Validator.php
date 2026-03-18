<?php

declare(strict_types=1);

namespace Sauerkraut\Validation;

use Sauerkraut\Validation\Rules\Nullable;

class Validator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, Rule[]> $rules
     */
    public static function validate(array $data, array $rules): ValidationResult
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            if (self::isNullable($fieldRules) && self::isEmpty($value)) {
                $validated[$field] = $value;
                continue;
            }

            $fieldErrors = self::validateField($field, $value, $data, $fieldRules);

            if ($fieldErrors === []) {
                $validated[$field] = $value;
            } else {
                $errors[$field] = $fieldErrors;
            }
        }

        return new ValidationResult($errors, $validated);
    }

    private static function isNullable(array $rules): bool
    {
        foreach ($rules as $rule) {
            if ($rule instanceof Nullable) {
                return true;
            }
        }

        return false;
    }

    private static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    /** @return string[] */
    private static function validateField(string $field, mixed $value, array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $rule) {
            if ($rule instanceof Nullable) {
                continue;
            }

            $error = $rule->validate($field, $value, $data);

            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }
}
