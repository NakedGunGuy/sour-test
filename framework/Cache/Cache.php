<?php

declare(strict_types=1);

namespace Sauerkraut\Cache;

class Cache
{
    public function __construct(private string $cacheDir)
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $path = $this->path($key);

        if (!file_exists($path)) {
            return $default;
        }

        $data = unserialize(file_get_contents($path));

        if ($this->isExpired($data)) {
            unlink($path);
            return $default;
        }

        return $data['value'];
    }

    public function put(string $key, mixed $value, int $seconds = 3600): void
    {
        $data = serialize([
            'value' => $value,
            'expires_at' => time() + $seconds,
        ]);

        file_put_contents($this->path($key), $data, LOCK_EX);
    }

    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    public function forget(string $key): void
    {
        $path = $this->path($key);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function remember(string $key, int $seconds, callable $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->put($key, $value, $seconds);

        return $value;
    }

    public function flush(): void
    {
        $files = glob($this->cacheDir . '/*.cache');

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            unlink($file);
        }
    }

    private function path(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }

    private function isExpired(array $data): bool
    {
        return isset($data['expires_at']) && time() > $data['expires_at'];
    }
}
