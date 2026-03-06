<?php

declare(strict_types=1);

namespace Sauerkraut\Database\Schema;

class ForeignKey
{
    public function __construct(
        public readonly string $column,
        public readonly string $referencesTable,
        public readonly string $referencesColumn,
    ) {}
}
