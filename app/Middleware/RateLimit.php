<?php

declare(strict_types=1);

namespace App\Middleware;

use Sauerkraut\Http\Middleware;
use Sauerkraut\Request;
use Sauerkraut\Response;

class RateLimit implements Middleware
{
    private int $maxAttempts = 60;
    private int $decayMinutes = 1;

    public function handle(Request $request, \Closure $next): Response
    {
        $key = $this->resolveKey($request);
        $file = $this->storagePath($key);

        $data = $this->loadData($file);
        $now = time();

        // Reset if window expired
        if ($now - $data['window_start'] > $this->decayMinutes * 60) {
            $data = ['attempts' => 0, 'window_start' => $now];
        }

        $data['attempts']++;
        $this->saveData($file, $data);

        if ($data['attempts'] > $this->maxAttempts) {
            $retryAfter = ($data['window_start'] + $this->decayMinutes * 60) - $now;
            return Response::json(['error' => 'Too many requests.'], 429)
                ->withHeader('Retry-After', (string) max(1, $retryAfter))
                ->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
                ->withHeader('X-RateLimit-Remaining', '0');
        }

        $response = $next($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, $this->maxAttempts - $data['attempts']));
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
