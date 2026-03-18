<?php

declare(strict_types=1);

namespace Sauerkraut\Http;

use Sauerkraut\Request;
use Sauerkraut\Response;

class SecurityHeaders implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        $response = $next($request);

        return $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'SAMEORIGIN')
            ->withHeader('X-XSS-Protection', '0')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
