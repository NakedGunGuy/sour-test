# Controllers

Controllers live in `app/Controllers/` and extend the base `Controller` class.

## Creating a Controller

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use Sauerkraut\Request;
use Sauerkraut\Response;

class PostController extends Controller
{
    public function index(Request $request): Response
    {
        $posts = $this->app->db()->queryAll('SELECT * FROM posts ORDER BY id DESC');

        return $this->view('posts/index', ['posts' => $posts]);
    }

    public function show(Request $request, string $id): Response
    {
        $post = $this->app->db()->queryOne('SELECT * FROM posts WHERE id = ?', [$id]);

        if (!$post) {
            return Response::html('<h1>404</h1>', 404);
        }

        return $this->view('posts/show', ['post' => $post]);
    }

    public function store(Request $request): Response
    {
        // Use $this->app->db() for database access
        $this->app->db()->execute(
            'INSERT INTO posts (title, body) VALUES (?, ?)',
            [$request->post('title'), $request->post('body')],
        );

        return $this->redirect('/posts');
    }
}
```

## Base Controller Methods

| Method | Description |
|--------|-------------|
| `$this->view($page, $data)` | Render a view template |
| `$this->json($data, $status)` | Return JSON response |
| `$this->redirect($url, $status)` | Redirect response |
| `$this->app` | Access the App instance |
| `$this->app->db()` | Database connection |
| `$this->app->config($key)` | Read config values |

## Registering Routes

```php
// routes/web.php
$router->get('/posts', [App\Controllers\PostController::class, 'index']);
$router->post('/posts', [App\Controllers\PostController::class, 'store']);
$router->get('/posts/{id}', [App\Controllers\PostController::class, 'show']);
```

The App instance is automatically injected into the controller constructor.
