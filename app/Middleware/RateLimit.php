<?php

declare(strict_types=1);

namespace App\Middleware;

use Sauerkraut\Http\Middleware;
use Sauerkraut\Request;
use Sauerkraut\Response;

class RateLimit implements Middleware
{
    private const int MAX_ATTEMPTS = 60;
    private const int DECAY_SECONDS = 60;

    public function handle(Request $request, \Closure $next): Response
    {
        $key = $this->resolveKey($request);
        $file = $this->storagePath($key);

        $data = $this->atomicUpdate($file);

        if ($data['attempts'] > self::MAX_ATTEMPTS) {
            $retryAfter = ($data['window_start'] + self::DECAY_SECONDS) - time();

            return Response::json(['error' => 'Too many requests.'], 429)
                ->withHeader('Retry-After', (string) max(1, $retryAfter))
                ->withHeader('X-RateLimit-Limit', (string) self::MAX_ATTEMPTS)
                ->withHeader('X-RateLimit-Remaining', '0');
        }

        $remaining = max(0, self::MAX_ATTEMPTS - $data['attempts']);

        return $next($request)
            ->withHeader('X-RateLimit-Limit', (string) self::MAX_ATTEMPTS)
            ->withHeader('X-RateLimit-Remaining', (string) $remaining);
    }

    private function atomicUpdate(string $file): array
    {
        $handle = fopen($file, 'c+');

        if ($handle === false) {
            return ['attempts' => 1, 'window_start' => time()];
        }

        flock($handle, LOCK_EX);

        $content = stream_get_contents($handle);
        $data = $content ? json_decode($content, true) : null;
        $now = time();

        if (!$data || ($now - $data['window_start']) > self::DECAY_SECONDS) {
            $data = ['attempts' => 0, 'window_start' => $now];
        }

        $data['attempts']++;

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($data));
        flock($handle, LOCK_UN);
        fclose($handle);

        return $data;
    }

    private function resolveKey(Request $request): string
    {
        return 'rate_' . md5($request->ip() . '|' . $request->path());
    }

    private function storagePath(string $key): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return "{$dir}/{$key}.json";
    }
}
