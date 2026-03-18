<?php

declare(strict_types=1);

namespace App\Middleware;

use Sauerkraut\Http\Middleware;
use Sauerkraut\Request;
use Sauerkraut\Response;

class Cors implements Middleware
{
    private const string ALLOWED_ORIGIN = '*';
    private const string ALLOWED_METHODS = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
    private const string ALLOWED_HEADERS = 'Content-Type, Authorization';
    private const string MAX_AGE = '86400';

    public function handle(Request $request, \Closure $next): Response
    {
        if ($request->method() === 'OPTIONS') {
            return $this->withCorsHeaders(Response::empty())
                ->withHeader('Access-Control-Max-Age', self::MAX_AGE);
        }

        return $this->withCorsHeaders($next($request));
    }

    private function withCorsHeaders(Response $response): Response
    {
        $origin = config('cors.allowed_origin', self::ALLOWED_ORIGIN);
        $methods = config('cors.allowed_methods', self::ALLOWED_METHODS);
        $headers = config('cors.allowed_headers', self::ALLOWED_HEADERS);

        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Methods', $methods)
            ->withHeader('Access-Control-Allow-Headers', $headers);
    }
}
