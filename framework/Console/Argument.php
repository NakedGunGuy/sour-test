<?php

declare(strict_types=1);

namespace Sauerkraut\Console;

readonly class Argument
{
    public function __construct(
        public string $name,
        public string $description = '',
        public bool $required = false,
        public ?string $default = null,
    ) {}
}
