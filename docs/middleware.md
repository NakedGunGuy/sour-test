# Middleware

Middleware intercepts HTTP requests before they reach the controller.

## Creating Middleware

Implement the `Middleware` interface:

```php
<?php

declare(strict_types=1);

namespace App\Middleware;

use Sauerkraut\Http\Middleware;
use Sauerkraut\Request;
use Sauerkraut\Response;

class LogRequests implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        // Before the request is handled
        $start = microtime(true);

        // Pass to the next middleware/controller
        $response = $next($request);

        // After the response is created
        $duration = microtime(true) - $start;
        error_log("{$request->method()} {$request->path()} - {$duration}ms");

        return $response;
    }
}
```

## Applying Middleware

**To specific routes:**
```php
$router->middleware(AuthMiddleware::class)->get('/profile', [ProfileController::class, 'show']);
```

**To route groups:**
```php
$router->group(['middleware' => [AuthMiddleware::class, RateLimit::class]], function (Router $router) {
    // All routes in this group go through both middleware
});
```

## Built-in Middleware

| Middleware | Description |
|-----------|-------------|
| `App\Middleware\Cors` | CORS headers (configurable via `config('cors.allowed_origin')`) |
| `App\Middleware\Csrf` | CSRF protection with token rotation |
| `App\Middleware\RateLimit` | Rate limiting (60 req/min per IP, atomic file locks) |
| `Sauerkraut\Http\SecurityHeaders` | X-Frame-Options, X-Content-Type-Options, Referrer-Policy |
| `Sauerkraut\Auth\AuthMiddleware` | Redirects guests to `/login` |
| `Sauerkraut\Auth\GuestMiddleware` | Redirects authenticated users to `/` |
