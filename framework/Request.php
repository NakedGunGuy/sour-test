<?php

declare(strict_types=1);

namespace Sauerkraut;

class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $post;
    private array $server;
    private array $cookies;
    private array $files;
    private array $headers;
    private ?string $body;
    private array $routeParams = [];

    public function __construct(
        string $method,
        string $path,
        array $query = [],
        array $post = [],
        array $server = [],
        array $cookies = [],
        array $files = [],
        ?string $body = null,
    ) {
        $this->method = strtoupper($method);
        $this->path = '/' . trim($path, '/');
        $this->query = $query;
        $this->post = $post;
        $this->server = $server;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->body = $body;
        $this->headers = $this->parseHeaders($server);
    }

    public static function capture(): static
    {
        $method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $body = file_get_contents('php://input') ?: null;

        return new static($method, $path, $_GET, $_POST, $_SERVER, $_COOKIE, $_FILES, $body);
    }

    private function parseHeaders(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            }
        }
        if (isset($server['CONTENT_TYPE'])) {
            $headers['content-type'] = $server['CONTENT_TYPE'];
        }
        if (isset($server['CONTENT_LENGTH'])) {
            $headers['content-length'] = $server['CONTENT_LENGTH'];
        }
        return $headers;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function post(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    public function input(string $key = null, mixed $default = null): mixed
    {
        $all = array_merge($this->query, $this->post, $this->json());

        if ($key === null) {
            return $all;
        }
        return $all[$key] ?? $default;
    }

    public function json(): array
    {
        if (!$this->body || !str_contains($this->header('content-type', ''), 'json')) {
            return [];
        }

        $decoded = json_decode($this->body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    public function hasValidJson(): bool
    {
        if (!$this->body || !str_contains($this->header('content-type', ''), 'json')) {
            return false;
        }

        json_decode($this->body);

        return json_last_error() === JSON_ERROR_NONE;
    }

    public function body(): ?string
    {
        return $this->body;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function cookie(string $name, mixed $default = null): mixed
    {
        return $this->cookies[$name] ?? $default;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function files(): array
    {
        return $this->files;
    }

    public function server(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->server;
        }
        return $this->server[$key] ?? $default;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function params(): array
    {
        return $this->routeParams;
    }

    public function isJson(): bool
    {
        return str_contains($this->header('content-type', ''), 'json');
    }

    public function expectsJson(): bool
    {
        return str_contains($this->header('accept', ''), 'json');
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization', '');
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }

    public function ip(): string
    {
        return $this->server['HTTP_X_FORWARDED_FOR']
            ?? $this->server['REMOTE_ADDR']
            ?? '127.0.0.1';
    }
}
