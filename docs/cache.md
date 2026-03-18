# Cache

File-based caching with TTL expiry.

## Usage

```php
use Sauerkraut\Cache\Cache;

$cache = new Cache($this->app->basePath('storage/cache'));

// Store a value for 1 hour
$cache->put('user:42', $userData, 3600);

// Retrieve (returns default if missing or expired)
$user = $cache->get('user:42');
$user = $cache->get('user:42', 'fallback');

// Check existence
if ($cache->has('user:42')) { ... }

// Delete
$cache->forget('user:42');

// Remember pattern — compute only if not cached
$stats = $cache->remember('dashboard:stats', 300, function () {
    return $this->computeExpensiveStats();
});

// Clear everything
$cache->flush();
```

## API

| Method | Description |
|--------|-------------|
| `get($key, $default)` | Retrieve value or default |
| `put($key, $value, $seconds)` | Store with TTL (default 1 hour) |
| `has($key)` | Check if key exists and not expired |
| `forget($key)` | Delete a key |
| `remember($key, $seconds, $callback)` | Get or compute and cache |
| `flush()` | Delete all cached values |

## Storage

Cached values are serialized to `storage/cache/` as `.cache` files. File writes use `LOCK_EX` for atomicity. Expired values are cleaned up on next read.
