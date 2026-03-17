<?php

declare(strict_types=1);

namespace Sauerkraut\Database\Schema;

class ManyToManyRelation
{
    public function __construct(
        public readonly string $junctionTable,
        public readonly string $relatedTable,
        public readonly ForeignKey $localFk,
        public readonly ForeignKey $remoteFk,
    ) {}
}
