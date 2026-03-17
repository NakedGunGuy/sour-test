<?php

declare(strict_types=1);

namespace Sauerkraut\Database\Schema;

class OneToManyRelation
{
    public function __construct(
        public readonly string $table,
        public readonly ForeignKey $foreignKey,
    ) {}
}
