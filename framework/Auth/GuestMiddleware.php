<?php

declare(strict_types=1);

namespace Sauerkraut\Auth;

use Sauerkraut\Http\Middleware;
use Sauerkraut\Request;
use Sauerkraut\Response;

class GuestMiddleware implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        if (Auth::check()) {
            return Response::redirect('/');
        }

        return $next($request);
    }
}
