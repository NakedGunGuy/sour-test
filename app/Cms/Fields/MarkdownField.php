<?php

declare(strict_types=1);

namespace App\Cms\Fields;

use Sauerkraut\CMS\Field;
use Sauerkraut\CMS\FieldType;

class MarkdownField implements FieldType
{
    public function render(Field $field, mixed $value, ?string $error = null): string
    {
        $id = 'field-' . $field->name;
        $hasError = !empty($error);
        $escapedValue = htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escapedLabel = htmlspecialchars($field->label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escapedId = htmlspecialchars($id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escapedName = htmlspecialchars($field->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $required = $field->required ? 'required' : '';
        $readonly = $field->readonly ? 'readonly' : '';
        $errorClass = $hasError ? ' has-error' : '';

        $html = <<<HTML
        <div class="form-field{$errorClass}">
            <label class="form-label" for="{$escapedId}">
                {$escapedLabel}
                {$this->requiredBadge($field)}
            </label>
            <p style="font-size: var(--text-sm); color: var(--text-2); margin-bottom: var(--space-xs);">
                Supports Markdown formatting
            </p>
            <textarea
                class="form-input form-textarea"
                id="{$escapedId}"
                name="{$escapedName}"
                rows="12"
                style="font-family: var(--font-mono); font-size: var(--text-sm);"
                {$required}
                {$readonly}
            >{$escapedValue}</textarea>
        HTML;

        if ($hasError) {
            $escapedError = htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html .= "<p class=\"form-error\">{$escapedError}</p>";
        }

        $html .= '</div>';

        return $html;
    }

    public function cast(mixed $value, Field $field): mixed
    {
        return (string) ($value ?? '');
    }

    private function requiredBadge(Field $field): string
    {
        return $field->required ? '<span class="form-required">*</span>' : '';
    }
}
