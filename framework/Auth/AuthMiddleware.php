<?php

declare(strict_types=1);

namespace Sauerkraut\Auth;

use Sauerkraut\Http\Middleware;
use Sauerkraut\Request;
use Sauerkraut\Response;

class AuthMiddleware implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        if (Auth::guest()) {
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Unauthenticated.'], 401);
            }

            return Response::redirect('/login');
        }

        return $next($request);
    }
}
