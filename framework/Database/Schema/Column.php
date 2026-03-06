<?php

declare(strict_types=1);

namespace Sauerkraut\Database\Schema;

class Column
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly bool $notNull,
        public readonly mixed $default,
        public readonly bool $primaryKey,
    ) {}
}
