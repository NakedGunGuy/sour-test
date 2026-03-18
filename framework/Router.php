<?php

declare(strict_types=1);

namespace Sauerkraut;

class Router
{
    /** @var Route[] */
    private array $routes = [];

    /** @var array<string, Route> */
    private array $namedRoutes = [];

    private array $groupStack = [];

    public function get(string $pattern, mixed $handler): self
    {
        return $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, mixed $handler): self
    {
        return $this->addRoute('POST', $pattern, $handler);
    }

    public function put(string $pattern, mixed $handler): self
    {
        return $this->addRoute('PUT', $pattern, $handler);
    }

    public function patch(string $pattern, mixed $handler): self
    {
        return $this->addRoute('PATCH', $pattern, $handler);
    }

    public function delete(string $pattern, mixed $handler): self
    {
        return $this->addRoute('DELETE', $pattern, $handler);
    }

    private string $pendingName = '';
    private array $pendingMiddleware = [];

    public function name(string $name): self
    {
        $this->pendingName = $name;
        return $this;
    }

    public function middleware(string|array $middleware): self
    {
        $this->pendingMiddleware = array_merge(
            $this->pendingMiddleware,
            (array) $middleware,
        );
        return $this;
    }

    private function addRoute(string $method, string $pattern, mixed $handler): self
    {
        $prefix = $this->currentPrefix();
        $fullPattern = rtrim($prefix . '/' . ltrim($pattern, '/'), '/') ?: '/';
        $groupMiddleware = $this->currentMiddleware();

        $name = $this->pendingName ?: null;
        $middleware = array_merge($groupMiddleware, $this->pendingMiddleware);

        $app = $this->currentApp();
        $route = new Route($method, $fullPattern, $handler, $middleware, $name, $app);
        $this->routes[] = $route;

        if ($name) {
            $this->namedRoutes[$name] = $route;
        }

        // Reset pending state
        $this->pendingName = '';
        $this->pendingMiddleware = [];

        return $this;
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function currentPrefix(): string
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }
        return $prefix;
    }

    private function currentMiddleware(): array
    {
        $middleware = [];
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $middleware = array_merge($middleware, (array) $group['middleware']);
            }
        }
        return $middleware;
    }

    private function currentApp(): string
    {
        for ($i = count($this->groupStack) - 1; $i >= 0; $i--) {
            if (isset($this->groupStack[$i]['app'])) {
                return $this->groupStack[$i]['app'];
            }
        }
        return 'frontend';
    }

    /**
     * @return array{Route, array}|null Matched route and params, or null
     */
    public function match(string $method, string $path): ?array
    {
        $path = '/' . trim($path, '/');

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        foreach ($this->routes as $route) {
            $params = $route->matches($method, $path);
            if ($params !== null) {
                return [$route, $params];
            }
        }

        return null;
    }

    public function hasMatchingPath(string $path): bool
    {
        $path = '/' . trim($path, '/');

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        foreach ($this->routes as $route) {
            if ($route->matchesPath($path)) {
                return true;
            }
        }

        return false;
    }

    public function url(string $name, array $params = []): string
    {
        $route = $this->namedRoutes[$name]
            ?? throw new \RuntimeException("Route [{$name}] not found.");

        return $route->url($params);
    }

    public function routes(): array
    {
        return $this->routes;
    }
}
