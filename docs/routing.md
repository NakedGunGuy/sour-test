# Routing

Routes are defined in `routes/web.php`. The router supports GET, POST, PUT, PATCH, and DELETE methods.

## Defining Routes

```php
$router->get('/users', [UserController::class, 'index']);
$router->post('/users', [UserController::class, 'store']);
$router->get('/users/{id}', [UserController::class, 'show']);
$router->put('/users/{id}', [UserController::class, 'update']);
$router->delete('/users/{id}', [UserController::class, 'destroy']);
```

## Closure Routes

```php
$router->get('/hello', function (Request $request) {
    return Response::html('<h1>Hello World</h1>');
});
```

## Route Parameters

Parameters are defined with `{name}` and passed to the handler:

```php
$router->get('/posts/{slug}', function (Request $request, string $slug) {
    return Response::html("Post: {$slug}");
});
```

## Named Routes

```php
$router->name('user.show')->get('/users/{id}', [UserController::class, 'show']);

// Generate URL
$url = route('user.show', ['id' => 42]); // /users/42
```

## Route Groups

Group routes with shared prefix and middleware:

```php
$router->group(['prefix' => 'admin', 'middleware' => [AuthMiddleware::class]], function (Router $router) {
    $router->get('/dashboard', [AdminController::class, 'dashboard']);
    $router->get('/users', [AdminController::class, 'users']);
});
```

## Route Middleware

Apply middleware to specific routes:

```php
$router->middleware(AuthMiddleware::class)->get('/profile', [ProfileController::class, 'show']);
```

## Listing Routes

```bash
php sauerkraut routes:list
```

Shows all registered routes with method, pattern, name, middleware, and app.

## How It Works

- Routes are matched in registration order
- Trailing slashes are normalized
- If a path matches but the method doesn't, a 405 is returned (not 404)
