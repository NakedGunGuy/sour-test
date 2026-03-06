<?php

declare(strict_types=1);

namespace Sauerkraut;

class Route
{
    private string $method;
    private string $pattern;
    private mixed $handler;
    private array $middleware;
    private ?string $name;
    private string $app;
    private string $regex;
    private array $paramNames;

    public function __construct(string $method, string $pattern, mixed $handler, array $middleware = [], ?string $name = null, string $app = 'frontend')
    {
        $this->method = strtoupper($method);
        $this->pattern = $pattern;
        $this->handler = $handler;
        $this->middleware = $middleware;
        $this->name = $name;
        $this->app = $app;

        [$this->regex, $this->paramNames] = $this->compile($pattern);
    }

    private function compile(string $pattern): array
    {
        $paramNames = [];

        // Match {param} segments
        $regex = preg_replace_callback('/\{([a-zA-Z_]+)\}/', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '([^/]+)';
        }, $pattern);

        return ['#^' . $regex . '$#', $paramNames];
    }

    public function matches(string $method, string $path): ?array
    {
        if ($this->method !== strtoupper($method)) {
            return null;
        }

        if (preg_match($this->regex, $path, $matches)) {
            array_shift($matches); // Remove full match
            return array_combine($this->paramNames, $matches) ?: [];
        }

        return null;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function pattern(): string
    {
        return $this->pattern;
    }

    public function handler(): mixed
    {
        return $this->handler;
    }

    public function middleware(): array
    {
        return $this->middleware;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function app(): string
    {
        return $this->app;
    }

    public function url(array $params = []): string
    {
        $url = $this->pattern;
        foreach ($params as $key => $value) {
            $url = str_replace("{{$key}}", (string) $value, $url);
        }
        return $url;
    }
}
