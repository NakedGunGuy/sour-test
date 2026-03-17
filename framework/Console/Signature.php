<?php

declare(strict_types=1);

namespace Sauerkraut\Console;

readonly class Signature
{
    /** @param Argument[] $arguments */
    /** @param Option[] $options */
    public function __construct(
        public string $name,
        public string $description = '',
        public array $arguments = [],
        public array $options = [],
    ) {}
}
