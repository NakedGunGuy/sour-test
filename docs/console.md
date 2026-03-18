# Console Commands

The CLI is accessed via `php sauerkraut <command>`.

## Built-in Commands

| Command | Description |
|---------|-------------|
| `help` | List all commands |
| `publish` | Publish package files to your project |
| `migrate` | Run pending migrations |
| `migrate:rollback` | Roll back last batch |
| `migrate:status` | Show migration status |
| `make:migration` | Create a migration file |
| `db:seed` | Run database seeders |
| `make:seeder` | Create a seeder class |
| `schedule:run` | Run due scheduled commands |
| `schedule:list` | List scheduled commands |
| `routes:list` | List all routes |
| `env:key` | Generate encryption key |
| `env:encrypt` | Encrypt .env file |
| `env:decrypt` | Decrypt .env.encrypted |
| `test` | Run PHPUnit tests |

## Creating a Command

Create a class in `app/Commands/` — it's auto-discovered:

```php
<?php

declare(strict_types=1);

namespace App\Commands;

use Sauerkraut\Console\{Command, Input, Output, Signature, Argument, Option};

class GreetCommand extends Command
{
    public function signature(): Signature
    {
        return new Signature(
            name: 'greet',
            description: 'Greet a user',
            arguments: [
                new Argument('name', 'Who to greet', required: true),
            ],
            options: [
                new Option('loud', 'Shout the greeting', shortcut: 'l'),
            ],
        );
    }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->argument('name');
        $message = "Hello, {$name}!";

        if ($input->hasOption('loud')) {
            $message = strtoupper($message);
        }

        $output->success($message);

        return 0;
    }
}
```

```bash
php sauerkraut greet John          # Hello, John!
php sauerkraut greet John --loud   # HELLO, JOHN!
php sauerkraut greet John -l       # HELLO, JOHN!
```

## Output Methods

| Method | Description |
|--------|-------------|
| `$output->line($text)` | Plain text |
| `$output->info($text)` | Cyan text |
| `$output->success($text)` | Green text |
| `$output->error($text)` | Red text |
| `$output->warn($text)` | Yellow text |
| `$output->newLine($count)` | Empty lines |
| `$output->table($headers, $rows)` | Formatted table |

## Exit Codes

Return `0` for success, `1+` for errors.
