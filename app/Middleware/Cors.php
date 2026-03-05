<?php

declare(strict_types=1);

namespace App\Middleware;

use Sauerkraut\Http\Middleware;
use Sauerkraut\Request;
use Sauerkraut\Response;

class Cors implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        // Handle preflight
        if ($request->method() === 'OPTIONS') {
            return Response::empty()
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
                ->withHeader('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
}
