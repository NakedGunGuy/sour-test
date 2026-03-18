# HTTP Client

Make outgoing HTTP requests with a clean cURL wrapper.

## Basic Usage

```php
use Sauerkraut\Http\HttpClient;

$client = new HttpClient();

// GET request
$response = $client->get('https://api.example.com/users');

// GET with query parameters
$response = $client->get('https://api.example.com/users', ['page' => 2, 'limit' => 10]);

// POST JSON
$response = $client->post('https://api.example.com/users', [
    'name' => 'John',
    'email' => 'john@example.com',
]);

// PUT, PATCH, DELETE
$response = $client->put('https://api.example.com/users/42', ['name' => 'Jane']);
$response = $client->patch('https://api.example.com/users/42', ['name' => 'Jane']);
$response = $client->delete('https://api.example.com/users/42');

// POST form data
$response = $client->postForm('https://example.com/login', [
    'username' => 'john',
    'password' => 'secret',
]);
```

## Configuration

```php
// Custom timeout
$client = new HttpClient(timeout: 60);

// Default headers (immutable — returns new instance)
$client = (new HttpClient())
    ->withHeaders(['Authorization' => 'Bearer token123'])
    ->withTimeout(10);
```

## Response

```php
$response->status();        // 200
$response->body();          // raw body string
$response->json();          // decoded JSON
$response->header('content-type');  // header value

$response->isOk();          // 2xx
$response->isRedirect();    // 3xx
$response->isClientError(); // 4xx
$response->isServerError(); // 5xx
```

## Custom Requests

For full control, use `HttpRequest` directly:

```php
use Sauerkraut\Http\{HttpClient, HttpRequest};

$request = new HttpRequest(
    method: 'POST',
    url: 'https://api.example.com/webhook',
    body: '{"event":"test"}',
    headers: ['Content-Type' => 'application/json', 'X-Hook-Secret' => 'abc'],
);

$response = $client->send($request);
```
