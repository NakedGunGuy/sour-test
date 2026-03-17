<?php

declare(strict_types=1);

namespace Sauerkraut\Database;

abstract class Migration
{
    public function __construct(protected Connection $db) {}

    abstract public function up(): void;

    abstract public function down(): void;
}
