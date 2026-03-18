<?php

declare(strict_types=1);

namespace Sauerkraut\Database;

abstract class Seeder
{
    public function __construct(protected Connection $db) {}

    abstract public function run(): void;
}
