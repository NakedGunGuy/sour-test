<?php

declare(strict_types=1);

namespace Sauerkraut\CMS;

class Field
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $type,
        public readonly bool $required = false,
        public readonly mixed $default = null,
        public readonly array $options = [],
        public readonly bool $readonly = false,
    ) {}
}
