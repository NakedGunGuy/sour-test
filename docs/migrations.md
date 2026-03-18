# Migrations

Version-controlled database changes with batch-based rollback.

## Creating a Migration

```bash
php sauerkraut make:migration create_users_table
```

Generates `database/migrations/2026_03_18_143022_create_users_table.php`:

```php
<?php

declare(strict_types=1);

use Sauerkraut\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->db->execute('CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
    }

    public function down(): void
    {
        $this->db->execute('DROP TABLE IF EXISTS users');
    }
}
```

## Running Migrations

```bash
php sauerkraut migrate           # Run pending migrations
php sauerkraut migrate:rollback  # Roll back last batch
php sauerkraut migrate:status    # Show status of each migration
```

## How It Works

- Migrations run in timestamp order (filename-based)
- Each `migrate` run creates a new **batch** — all migrations in one run share a batch number
- `migrate:rollback` undoes the last batch (not individual migrations)
- Each migration's `up()` runs inside a transaction
- A `migrations` table tracks what has run (auto-created)
