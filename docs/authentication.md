# Authentication

Session-based authentication with bcrypt password hashing and brute-force protection.

## Login

```php
use Sauerkraut\Auth\Auth;

if (Auth::attempt($db, $email, $password)) {
    return Response::redirect('/dashboard');
}

// Failed — check if locked out
if (Auth::isLockedOut($email)) {
    return Response::html('Too many attempts. Try again in 5 minutes.', 429);
}
```

## Checking Auth State

```php
Auth::check();              // true if logged in
Auth::guest();              // true if NOT logged in
Auth::id();                 // user ID or null
Auth::user($db);            // full user row or null
Auth::remainingAttempts($email);  // attempts left before lockout
```

## Logout

```php
Auth::logout();  // destroys session
```

## Password Hashing

```php
$hash = Auth::hashPassword('secret');  // bcrypt
// Store $hash in the database
```

## View Helpers

Available in templates:

```php
<?php if (auth_check()): ?>
    <p>Welcome, <?= e(auth()['name']) ?></p>
<?php endif; ?>

<?= auth_id() ?>  <!-- user ID or null -->
```

## Protecting Routes

Use `AuthMiddleware` to require authentication:

```php
$router->group(['middleware' => [AuthMiddleware::class]], function (Router $router) {
    $router->get('/dashboard', [DashboardController::class, 'index']);
});
```

Use `GuestMiddleware` to redirect logged-in users (e.g., login page):

```php
$router->middleware(GuestMiddleware::class)->get('/login', [AuthController::class, 'showLogin']);
```

## Brute-Force Protection

- 5 failed attempts locks the account for 5 minutes
- Lockout is session-based per email
- Successful login clears the attempt counter
