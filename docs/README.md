# Sauerkraut Framework

A lightweight PHP 8.3+ framework built on fundamentals, best practices, and no magic.

## Requirements

- PHP 8.3+
- `ext-pdo`, `ext-mbstring`, `ext-sodium`
- Composer

## Quick Start

```bash
composer install
php sauerkraut help
```

**HTTP entry point:** `public/index.php`
**CLI entry point:** `sauerkraut`

## Documentation

- [Routing](routing.md)
- [Controllers](controllers.md)
- [Middleware](middleware.md)
- [Validation](validation.md)
- [Authentication](authentication.md)
- [Database](database.md)
- [Migrations](migrations.md)
- [Console Commands](console.md)
- [Scheduling](scheduling.md)
- [Views & Components](views.md)
- [UI Components](ui-components.md)
- [Events](events.md)
- [Mail](mail.md)
- [Cache](cache.md)
- [Logging](logging.md)
- [HTTP Client](http-client.md)
- [File Uploads](file-uploads.md)
- [Environment & Config](environment.md)
- [Testing](testing.md)

## Architecture

```
framework/          Sauerkraut\ namespace — core framework
app/                App\ namespace — your application code
  Controllers/      HTTP controllers
  Middleware/       Request middleware
  Commands/         CLI commands (auto-discovered)
packages/           Composer packages (UI components, CMS)
config/             Configuration files
database/
  migrations/       Database migrations
  seeders/          Data seeders
routes/             Route definitions
public/             Web root (index.php)
tests/              PHPUnit test suite
```

## Design Principles

- `declare(strict_types=1)` everywhere
- Constructor injection — no singletons in new code
- Readonly value objects for data
- 2 or fewer method arguments
- No magic, no flag parameters
- Early returns, guard clauses
- Named constants for all magic values
- Follows [clean-code-php](https://github.com/piotrplenik/clean-code-php)
