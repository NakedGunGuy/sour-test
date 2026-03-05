<?php

declare(strict_types=1);

namespace App\Middleware;

use Sauerkraut\Http\Session;
use Sauerkraut\Http\Middleware;
use Sauerkraut\Request;
use Sauerkraut\Response;

class Csrf implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            $this->ensureToken();
            return $next($request);
        }

        $token = $request->input('_token') ?? $request->header('x-csrf-token');
        $sessionToken = Session::get('_csrf_token');

        if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
            return Response::html('<h1>419 — CSRF Token Mismatch</h1>', 419);
        }

        return $next($request);
    }

    private function ensureToken(): void
    {
        if (!Session::has('_csrf_token')) {
            Session::set('_csrf_token', bin2hex(random_bytes(32)));
        }
    }

    public static function token(): string
    {
        Session::start();
        if (!Session::has('_csrf_token')) {
            Session::set('_csrf_token', bin2hex(random_bytes(32)));
        }
        return Session::get('_csrf_token');
    }
}
