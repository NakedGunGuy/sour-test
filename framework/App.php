<?php

declare(strict_types=1);

namespace Sauerkraut;

use Sauerkraut\Config\Config;
use Sauerkraut\Config\Env;
use Sauerkraut\Database\Connection;
use Sauerkraut\Database\MigrationRepository;
use Sauerkraut\Database\Migrator;
use Sauerkraut\Database\Schema\Inspector;
use Sauerkraut\View\AppContext;
use Sauerkraut\View\Component;
use Sauerkraut\View\View;

class App
{
    private array $bindings = [];
    private array $instances = [];
    private string $basePath;

    /** @var array<string, array> Discovered package configs keyed by package name */
    private array $packages = [];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
    }

    public static function boot(string $basePath): static
    {
        $app = new static($basePath);
        $app->registerErrorHandlers();
        Env::load($basePath);
        $app->bootstrapConfig();
        $app->bootstrapDatabase();
        $app->discoverPackages();
        $app->bootstrapView();
        return $app;
    }

    private function registerErrorHandlers(): void
    {
        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    private function bootstrapConfig(): void
    {
        $this->singleton(Config::class, fn () => new Config($this->basePath . '/config'));
        $this->validateConfig();
    }

    private function validateConfig(): void
    {
        $required = ['database.default', 'database.connections'];

        foreach ($required as $key) {
            if ($this->config($key) === null) {
                throw new \RuntimeException("Missing required config: {$key}");
            }
        }
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

        foreach ($this->packages as $package) {
            $this->registerPackageAssets($package);
        }

        $this->registerProjectComponents();
        $this->registerProjectOverrides();
    }

    private function registerPackageAssets(array $packageConfig): void
    {
        $installPath = $packageConfig['install_path'];
        $prefix = $packageConfig['components-prefix'] ?? '';
        $group = $packageConfig['components-group'] ?? 'frontend';

        $componentsPath = $packageConfig['components-path'] ?? null;
        if ($componentsPath && is_dir($installPath . '/' . $componentsPath)) {
            $this->registerComponents($installPath . '/' . $componentsPath, $prefix, $group);
        }

        $app = $this->appNameFromPackage($packageConfig);
        if (!$app) {
            return;
        }

        $pagesPath = $packageConfig['pages-path'] ?? null;
        if ($pagesPath && is_dir($installPath . '/' . $pagesPath)) {
            View::registerPagesDir($app, $installPath . '/' . $pagesPath);
        }

        $cssPath = $packageConfig['css'] ?? null;
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
        foreach ($this->packages as $package) {
            $app = $this->appNameFromPackage($package);
            if (!$app) {
                continue;
            }

            $prefix = $package['components-prefix'] ?? '';
            $group = $package['components-group'] ?? 'frontend';

            $overrideDir = $this->basePath . '/' . $app . '/components';
            if (is_dir($overrideDir)) {
                $this->registerComponents($overrideDir, $prefix, $group);
            }

            $overridePagesDir = $this->basePath . '/' . $app . '/pages';
            if (is_dir($overridePagesDir)) {
                View::registerPagesDir($app, $overridePagesDir);
            }

            if (!isset($package['css'])) {
                continue;
            }

            $overrideCss = $this->basePath . '/' . $app . '/' . basename($package['css']);
            if (file_exists($overrideCss)) {
                View::registerCssFile($app, $overrideCss);
            }
        }
    }

    /**
     * Derive the app name from a package config.
     * e.g. components-prefix "cms:" -> app name "cms"
     */
    private function appNameFromPackage(array $packageConfig): ?string
    {
        $prefix = $packageConfig['components-prefix'] ?? '';
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

    public function buildRouter(): Router
    {
        $router = new Router();
        $this->loadRoutes($router);

        return $router;
    }

    public function migrator(): Migrator
    {
        $db = $this->db();

        return new Migrator($db, new MigrationRepository($db), $this->basePath('database/migrations'));
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
        AppContext::set($this);

        $this->singleton(Router::class, fn () => $this->buildRouter());

        try {
            $request = Request::capture();
            $router = $this->router();
            $matched = $router->match($request->method(), $request->path());

            if ($matched === null) {
                $status = $router->hasMatchingPath($request->path()) ? 405 : 404;
                $this->sendErrorPage($status)->send();
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

        if ($this->config('app.debug', false)) {
            $message = htmlspecialchars($e->getMessage());
            $file = htmlspecialchars($e->getFile() . ':' . $e->getLine());
            $trace = htmlspecialchars($e->getTraceAsString());
            Response::html(
                "<h1>500 — {$message}</h1><p>{$file}</p><pre>{$trace}</pre>",
                500,
            )->send();
            return;
        }

        $this->sendErrorPage(500)->send();
    }

    private function sendErrorPage(int $status): Response
    {
        $errorPage = $this->basePath . "/resources/errors/{$status}.php";

        if (file_exists($errorPage)) {
            ob_start();
            require $errorPage;
            return Response::html(ob_get_clean(), $status);
        }

        $titles = [
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            419 => 'Page Expired',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
        ];

        $title = $titles[$status] ?? 'Error';

        return Response::html(
            "<!DOCTYPE html><html><head><title>{$status} {$title}</title>"
            . '<style>body{font-family:system-ui,sans-serif;display:flex;justify-content:center;align-items:center;'
            . 'min-height:100vh;margin:0;background:#f9fafb;color:#111}div{text-align:center}'
            . "h1{font-size:4rem;margin:0;opacity:.3}{$status}</h1>"
            . 'p{font-size:1.25rem;margin:.5rem 0;opacity:.6}</style></head>'
            . "<body><div><h1>{$status}</h1><p>{$title}</p></div></body></html>",
            $status,
        );
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

        foreach ($this->packages as $package) {
            $this->loadPackageRoutes($router, $package);
        }
    }

    private function loadPackageRoutes(Router $router, array $packageConfig): void
    {
        $routeFile = $packageConfig['routes'] ?? null;
        if (!$routeFile) {
            return;
        }

        $routePath = $packageConfig['install_path'] . '/' . $routeFile;

        $overridePath = $this->basePath . '/routes/' . basename($routeFile);
        if (file_exists($overridePath)) {
            $routePath = $overridePath;
        }

        if (!file_exists($routePath)) {
            return;
        }

        $app = $this->appNameFromPackage($packageConfig) ?? '';
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
            $controller = new $class($this);
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
