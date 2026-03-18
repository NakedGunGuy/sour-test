<?php

declare(strict_types=1);

namespace Sauerkraut\Http;

use Sauerkraut\Request;
use Sauerkraut\Response;

class ForceHttps implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        if ($this->isSecure($request)) {
            return $next($request);
        }

        $host = $request->header('host', 'localhost');
        $path = $request->path();
        $query = $request->query();
        $queryString = !empty($query) ? '?' . http_build_query($query) : '';

        return Response::redirect("https://{$host}{$path}{$queryString}", 301);
    }

    private function isSecure(Request $request): bool
    {
        if ($request->server('HTTPS') === 'on') {
            return true;
        }

        if ($request->header('x-forwarded-proto') === 'https') {
            return true;
        }

        return false;
    }
}
