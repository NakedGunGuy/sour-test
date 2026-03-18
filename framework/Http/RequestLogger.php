<?php

declare(strict_types=1);

namespace Sauerkraut\Http;

use Sauerkraut\Log\Logger;
use Sauerkraut\Request;
use Sauerkraut\Response;

class RequestLogger implements Middleware
{
    private static ?Logger $logger = null;

    public static function setLogger(Logger $logger): void
    {
        self::$logger = $logger;
    }

    public function handle(Request $request, \Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2);

        self::$logger?->info('{method} {path} {status} {duration}ms', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->status(),
            'duration' => $duration,
        ]);

        return $response;
    }
}
