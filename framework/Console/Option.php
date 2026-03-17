<?php

declare(strict_types=1);

namespace Sauerkraut\Console;

readonly class Option
{
    public function __construct(
        public string $name,
        public string $description = '',
        public ?string $shortcut = null,
        public bool $acceptsValue = false,
        public ?string $default = null,
    ) {}
}
