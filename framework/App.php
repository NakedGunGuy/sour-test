<?php

declare(strict_types=1);

namespace Sauerkraut;

use Sauerkraut\Config\Config;
use Sauerkraut\View\Component;
use Sauerkraut\View\View;

class App
{
    private static ?self $instance = null;

    private array $bindings = [];
    private array $instances = [];
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        static::$instance = $this;
    }

    public static function getInstance(): static
    {
        return static::$instance ?? throw new \RuntimeException('App has not been booted.');
    }

    public static function boot(string $basePath): static
    {
        $app = new static($basePath);
        $app->registerAutoloader();
        $app->bootstrapConfig();
        $app->bootstrapView();
        return $app;
    }

    private function registerAutoloader(): void
    {
        // Composer handles autoloading — see vendor/autoload.php
    }

    private function bootstrapConfig(): void
    {
        $this->singleton(Config::class, fn () => new Config($this->basePath . '/config'));
    }

    private function bootstrapView(): void
    {
        View::setBasePath($this->basePath);

        // 1. Vendor packages with sauerkraut components
        foreach ($this->vendorComponentPaths() as $dir) {
            if (is_dir($dir)) {
                $this->registerComponents($dir, '', 'front');
            }
        }

        // 2. Project components — override vendor
        $componentsDir = $this->basePath . '/components';
        if (is_dir($componentsDir)) {
            $this->registerComponents($componentsDir, '', 'front');
        }
    }

    private function vendorComponentPaths(): array
    {
        $installed = $this->basePath . '/vendor/composer/installed.json';
        if (!file_exists($installed)) {
            return [];
        }

        $data = json_decode(file_get_contents($installed), true);
        $packages = $data['packages'] ?? $data;

        $paths = [];
        foreach ($packages as $package) {
            $componentsPath = $package['extra']['sauerkraut']['components-path'] ?? null;
            if ($componentsPath) {
                $installPath = $this->basePath . '/vendor/' . $package['name'];
                $paths[] = $installPath . '/' . $componentsPath;
            }
        }

        return $paths;
    }

    private function registerComponents(string $dir, string $prefix, string $group): void
    {
        foreach (glob("{$dir}/*", GLOB_ONLYDIR) as $subdir) {
            $name = basename($subdir);
            $fullName = $prefix ? "{$prefix}/{$name}" : $name;

            if (file_exists("{$subdir}/{$name}.php")) {
                Component::register($fullName, $subdir, $group);
            } else {
                $this->registerComponents($subdir, $fullName, $group);
            }
        }
    }

    // --- Service Container ---

    public function singleton(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function make(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $this->instances[$abstract] = ($this->bindings[$abstract])($this);
            return $this->instances[$abstract];
        }

        throw new \RuntimeException("No binding for [{$abstract}].");
    }

    // --- Convenience Accessors ---

    public function config(string $key, mixed $default = null): mixed
    {
        return $this->make(Config::class)->get($key, $default);
    }

    public function router(): Router
    {
        return $this->make(Router::class);
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? '/' . ltrim($path, '/') : '');
    }

    // --- HTTP Lifecycle ---

    public function handleRequest(): void
    {
        require_once $this->basePath . '/framework/View/helpers.php';

        $this->singleton(Router::class, function () {
            $router = new Router();
            $this->loadRoutes($router);
            return $router;
        });

        try {
            $request = Request::capture();
            $router = $this->router();
            $matched = $router->match($request->method(), $request->path());

            if ($matched === null) {
                $response = Response::html('<h1>404 Not Found</h1>', 404);
                $response->send();
                return;
            }

            [$route, $params] = $matched;
            $request->setRouteParams($params);

            $middlewareClasses = $route->middleware();
            $handler = $route->handler();

            $pipeline = new Pipeline();
            $response = $pipeline->send($request)
                ->through($middlewareClasses)
                ->then(function (Request $request) use ($handler, $params) {
                    return $this->callHandler($handler, $request, $params);
                });

            $response->send();
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    private function handleException(\Throwable $e): void
    {
        $debug = $this->config('app.debug', false);

        $this->logException($e);

        if ($debug) {
            $message = htmlspecialchars($e->getMessage());
            $file = htmlspecialchars($e->getFile() . ':' . $e->getLine());
            $trace = htmlspecialchars($e->getTraceAsString());
            Response::html(
                "<h1>500 — {$message}</h1><p>{$file}</p><pre>{$trace}</pre>",
                500,
            )->send();
            return;
        }

        Response::html('<h1>500 Internal Server Error</h1>', 500)->send();
    }

    private function logException(\Throwable $e): void
    {
        $logDir = $this->basePath . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/' . date('Y-m-d') . '.log';
        $entry = sprintf(
            "[%s] %s: %s in %s:%d\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString(),
        );

        @file_put_contents($logFile, $entry, FILE_APPEND);
    }

    private function loadRoutes(Router $router): void
    {
        $webRoutes = $this->basePath . '/routes/web.php';

        if (file_exists($webRoutes)) {
            $router->group([], function (Router $router) use ($webRoutes) {
                require $webRoutes;
            });
        }
    }

    private function callHandler(mixed $handler, Request $request, array $params): Response
    {
        // Closure handler
        if ($handler instanceof \Closure) {
            $result = $handler($request, ...$params);
            return $this->toResponse($result);
        }

        // [Controller::class, 'method'] handler
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $controller = new $class();
            $result = $controller->$method($request, ...array_values($params));
            return $this->toResponse($result);
        }

        throw new \RuntimeException('Invalid route handler.');
    }

    private function toResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_string($result)) {
            return Response::html($result);
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        throw new \RuntimeException('Handler must return a Response, string, or array.');
    }
}
