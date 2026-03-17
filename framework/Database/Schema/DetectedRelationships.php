<?php

declare(strict_types=1);

namespace Sauerkraut\Database\Schema;

class DetectedRelationships
{
    /**
     * @param ForeignKey[] $manyToOne
     * @param OneToManyRelation[] $oneToMany
     * @param ManyToManyRelation[] $manyToMany
     */
    public function __construct(
        public readonly array $manyToOne,
        public readonly array $oneToMany,
        public readonly array $manyToMany,
    ) {}
}
