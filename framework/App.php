<?php

declare(strict_types=1);

namespace Sauerkraut;

use Sauerkraut\Config\Config;
use Sauerkraut\Config\Env;
use Sauerkraut\Database\Connection;
use Sauerkraut\Database\Schema\Inspector;
use Sauerkraut\View\Component;
use Sauerkraut\View\View;

class App
{
    private static ?self $instance = null;

    private array $bindings = [];
    private array $instances = [];
    private string $basePath;

    /** @var array<string, array> Discovered package configs keyed by package name */
    private array $packages = [];

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
        Env::load($basePath);
        $app->bootstrapConfig();
        $app->bootstrapDatabase();
        $app->discoverPackages();
        $app->bootstrapView();
        return $app;
    }

    private function bootstrapConfig(): void
    {
        $this->singleton(Config::class, fn () => new Config($this->basePath . '/config'));
    }

    private function bootstrapDatabase(): void
    {
        $this->singleton(Connection::class, function () {
            $default = $this->config('database.default', 'sqlite');
            $config = $this->config("database.connections.{$default}");
            if (!$config) {
                throw new \RuntimeException("Database connection [{$default}] not configured.");
            }
            return Connection::fromConfig($config);
        });

        $this->singleton(Inspector::class, function () {
            return new Inspector($this->db());
        });
    }

    private function discoverPackages(): void
    {
        $installed = $this->basePath . '/vendor/composer/installed.json';
        if (!file_exists($installed)) {
            return;
        }

        $data = json_decode(file_get_contents($installed), true);
        $packages = $data['packages'] ?? $data;

        $config = $this->make(Config::class);

        foreach ($packages as $package) {
            $sauerkraut = $package['extra']['sauerkraut'] ?? null;
            if (!$sauerkraut) {
                continue;
            }

            $installPath = $this->basePath . '/vendor/' . $package['name'];
            $this->packages[$package['name']] = array_merge($sauerkraut, [
                'install_path' => $installPath,
            ]);

            $configPath = $sauerkraut['config'] ?? null;
            if ($configPath) {
                $config->loadPackageConfig($installPath . '/' . $configPath);
            }
        }
    }

    private function bootstrapView(): void
    {
        View::setBasePath($this->basePath);

        foreach ($this->packages as $name => $pkg) {
            $this->registerPackageAssets($pkg);
        }

        $this->registerProjectComponents();
        $this->registerProjectOverrides();
    }

    private function registerPackageAssets(array $pkg): void
    {
        $installPath = $pkg['install_path'];
        $prefix = $pkg['components-prefix'] ?? '';
        $group = $pkg['components-group'] ?? 'frontend';

        $componentsPath = $pkg['components-path'] ?? null;
        if ($componentsPath) {
            $dir = $installPath . '/' . $componentsPath;
            if (is_dir($dir)) {
                $this->registerComponents($dir, $prefix, $group);
            }
        }

        $app = $this->appNameFromPackage($pkg);
        if (!$app) {
            return;
        }

        $pagesPath = $pkg['pages-path'] ?? null;
        if ($pagesPath) {
            $dir = $installPath . '/' . $pagesPath;
            if (is_dir($dir)) {
                View::registerPagesDir($app, $dir);
            }
        }

        $cssPath = $pkg['css'] ?? null;
        if ($cssPath) {
            View::registerCssFile($app, $installPath . '/' . $cssPath);
        }
    }

    private function registerProjectComponents(): void
    {
        $componentsDir = $this->basePath . '/frontend/components';
        if (is_dir($componentsDir)) {
            $this->registerComponents($componentsDir, '', 'frontend');
        }
    }

    private function registerProjectOverrides(): void
    {
        foreach ($this->packages as $name => $pkg) {
            $app = $this->appNameFromPackage($pkg);
            if (!$app) {
                continue;
            }

            $prefix = $pkg['components-prefix'] ?? '';
            $group = $pkg['components-group'] ?? 'frontend';

            $overrideDir = $this->basePath . '/' . $app . '/components';
            if (is_dir($overrideDir)) {
                $this->registerComponents($overrideDir, $prefix, $group);
            }

            $overridePagesDir = $this->basePath . '/' . $app . '/pages';
            if (is_dir($overridePagesDir)) {
                View::registerPagesDir($app, $overridePagesDir);
            }

            if (!isset($pkg['css'])) {
                continue;
            }

            $overrideCss = $this->basePath . '/' . $app . '/' . basename($pkg['css']);
            if (file_exists($overrideCss)) {
                View::registerCssFile($app, $overrideCss);
            }
        }
    }

    /**
     * Derive the app name from a package config.
     * e.g. components-prefix "cms:" -> app name "cms"
     */
    private function appNameFromPackage(array $pkg): ?string
    {
        $prefix = $pkg['components-prefix'] ?? '';
        if (!$prefix) {
            return null;
        }
        return rtrim($prefix, ':');
    }

    private function registerComponents(string $dir, string $prefix, string $group): void
    {
        foreach (glob("{$dir}/*", GLOB_ONLYDIR) as $subdir) {
            $name = basename($subdir);

            $fullName = $this->resolveComponentName($prefix, $name);

            if (file_exists("{$subdir}/{$name}.php")) {
                Component::register($fullName, $subdir, $group);
            } else {
                $this->registerComponents($subdir, $fullName, $group);
            }
        }
    }

    private function resolveComponentName(string $prefix, string $name): string
    {
        if (!$prefix) {
            return $name;
        }

        // Namespace prefix like "cms:" — no slash between prefix and name
        if (!str_contains($prefix, '/')) {
            return $prefix . $name;
        }

        return "{$prefix}/{$name}";
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

    public function db(): Connection
    {
        return $this->make(Connection::class);
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? '/' . ltrim($path, '/') : '');
    }

    /** @return array<string, array> */
    public function packages(): array
    {
        return $this->packages;
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
                Response::html('<h1>404 Not Found</h1>', 404)->send();
                return;
            }

            [$route, $params] = $matched;
            $request->setRouteParams($params);
            View::setCurrentApp($route->app());

            $pipeline = new Pipeline();
            $response = $pipeline->send($request)
                ->through($route->middleware())
                ->then(function (Request $request) use ($route, $params) {
                    return $this->callHandler($route->handler(), $request, $params);
                });

            $response->send();
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    private function handleException(\Throwable $e): void
    {
        $this->logException($e);

        if (!$this->config('app.debug', false)) {
            Response::html('<h1>500 Internal Server Error</h1>', 500)->send();
            return;
        }

        $message = htmlspecialchars($e->getMessage());
        $file = htmlspecialchars($e->getFile() . ':' . $e->getLine());
        $trace = htmlspecialchars($e->getTraceAsString());
        Response::html(
            "<h1>500 — {$message}</h1><p>{$file}</p><pre>{$trace}</pre>",
            500,
        )->send();
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

        foreach ($this->packages as $name => $pkg) {
            $this->loadPackageRoutes($router, $pkg);
        }
    }

    private function loadPackageRoutes(Router $router, array $pkg): void
    {
        $routeFile = $pkg['routes'] ?? null;
        if (!$routeFile) {
            return;
        }

        $routePath = $pkg['install_path'] . '/' . $routeFile;

        $overridePath = $this->basePath . '/routes/' . basename($routeFile);
        if (file_exists($overridePath)) {
            $routePath = $overridePath;
        }

        if (!file_exists($routePath)) {
            return;
        }

        $app = $this->appNameFromPackage($pkg) ?? '';
        $router->group(['prefix' => $app, 'app' => $app ?: 'frontend'], function (Router $router) use ($routePath) {
            require $routePath;
        });
    }

    private function callHandler(mixed $handler, Request $request, array $params): Response
    {
        if ($handler instanceof \Closure) {
            return $this->toResponse($handler($request, ...$params));
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $controller = new $class();
            return $this->toResponse($controller->$method($request, ...array_values($params)));
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
