# Logging

Channel-based logging with configurable minimum levels.

## Configuration

`config/logging.php`:

```php
return [
    'default' => 'app',
    'channels' => [
        'app'      => ['path' => 'storage/logs', 'level' => 'debug'],
        'auth'     => ['path' => 'storage/logs', 'level' => 'info'],
        'query'    => ['path' => 'storage/logs', 'level' => 'debug'],
        'security' => ['path' => 'storage/logs', 'level' => 'warning'],
    ],
];
```

## Usage

```php
use Sauerkraut\Log\{LogManager, Logger};

// Via LogManager (multi-channel)
$logManager = LogManager::fromConfig($basePath, $this->app->config('logging'));
$logManager->channel('auth')->info('User {email} logged in', ['email' => $email]);
$logManager->channel('security')->warning('Brute force detected from {ip}', ['ip' => $ip]);
$logManager->default()->error('Something broke');

// Direct Logger (single channel)
$logger = new Logger('app', $this->app->basePath('storage/logs'), 'debug');
$logger->info('Server started');
```

## Log Levels

From lowest to highest priority:

| Level | When to use |
|-------|-------------|
| `debug` | Detailed diagnostic info (queries, variable dumps) |
| `info` | General events (user login, job completed) |
| `warning` | Potential problems (disk space low, deprecated usage) |
| `error` | Failures that need attention (exception caught, API failure) |

Messages below the channel's minimum level are silently discarded.

## Context Interpolation

Use `{placeholder}` syntax:

```php
$logger->info('User {name} from {ip}', ['name' => 'John', 'ip' => '1.2.3.4']);
// Output: [2026-03-18 12:00:00] app.info: User John from 1.2.3.4
```

Supports strings, numbers, booleans (`true`/`false`), null, and arrays (JSON-encoded).

## Environment Strategy

- **Dev:** All channels at `debug` — see everything
- **Staging:** `app` at `info`, `query` at `warning` — less noise
- **Production:** Everything at `warning` or `error` — only problems

Just change the config — no code changes needed.
