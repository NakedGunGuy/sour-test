<?php

declare(strict_types=1);

namespace Sauerkraut\CMS;

interface FieldType
{
    /**
     * Render the form input HTML.
     */
    public function render(Field $field, mixed $value, ?string $error = null): string;

    /**
     * Cast the submitted value before saving to the database.
     * Return null to use the default casting logic.
     */
    public function cast(mixed $value, Field $field): mixed;
}
