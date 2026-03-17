<?php

declare(strict_types=1);

namespace App\Middleware;

use Sauerkraut\Http\Middleware;
use Sauerkraut\Request;
use Sauerkraut\Response;

class RateLimit implements Middleware
{
    private const MAX_ATTEMPTS = 60;
    private const DECAY_MINUTES = 1;
    private const SECONDS_PER_MINUTE = 60;
    private const CACHE_DIR_PERMISSIONS = 0755;

    public function handle(Request $request, \Closure $next): Response
    {
        $key = $this->resolveKey($request);
        $file = $this->storagePath($key);

        $data = $this->loadData($file);
        $now = time();
        $windowSeconds = self::DECAY_MINUTES * self::SECONDS_PER_MINUTE;

        if ($now - $data['window_start'] > $windowSeconds) {
            $data = ['attempts' => 0, 'window_start' => $now];
        }

        $data['attempts']++;
        $this->saveData($file, $data);

        if ($data['attempts'] > self::MAX_ATTEMPTS) {
            $retryAfter = ($data['window_start'] + $windowSeconds) - $now;
            return Response::json(['error' => 'Too many requests.'], 429)
                ->withHeader('Retry-After', (string) max(1, $retryAfter))
                ->withHeader('X-RateLimit-Limit', (string) self::MAX_ATTEMPTS)
                ->withHeader('X-RateLimit-Remaining', '0');
        }

        $response = $next($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) self::MAX_ATTEMPTS)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, self::MAX_ATTEMPTS - $data['attempts']));
    }

    private function resolveKey(Request $request): string
    {
        return 'rate_' . md5($request->ip() . '|' . $request->path());
    }

    private function storagePath(string $key): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($dir)) {
            mkdir($dir, self::CACHE_DIR_PERMISSIONS, true);
        }
        return "{$dir}/{$key}.json";
    }

    private function loadData(string $file): array
    {
        if (!file_exists($file)) {
            return ['attempts' => 0, 'window_start' => time()];
        }
        return json_decode(file_get_contents($file), true) ?? ['attempts' => 0, 'window_start' => time()];
    }

    private function saveData(string $file, array $data): void
    {
        file_put_contents($file, json_encode($data));
    }
}
