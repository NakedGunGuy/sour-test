<?php

declare(strict_types=1);

namespace Sauerkraut\Console\Commands;

use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Signature;
use Sauerkraut\Router;

class RoutesListCommand extends Command
{
    public function signature(): Signature
    {
        return new Signature(
            name: 'routes:list',
            description: 'List all registered routes',
        );
    }

    public function handle(Input $input, Output $output): int
    {
        require_once $this->app->basePath('framework/View/helpers.php');

        $router = new Router();
        $this->loadRoutes($router);
        $routes = $router->routes();

        if (empty($routes)) {
            $output->info('No routes registered.');
            return 0;
        }

        $output->newLine();

        $headers = ['Method', 'Pattern', 'Name', 'Middleware', 'App'];
        $rows = [];

        foreach ($routes as $route) {
            $rows[] = [
                $route->method(),
                $route->pattern(),
                $route->name() ?? '',
                implode(', ', array_map(fn (string $m) => $this->shortName($m), $route->middleware())),
                $route->app(),
            ];
        }

        $output->table($headers, $rows);
        $output->newLine();

        return 0;
    }

    private function loadRoutes(Router $router): void
    {
        $webRoutes = $this->app->basePath('routes/web.php');

        if (file_exists($webRoutes)) {
            $router->group([], function (Router $router) use ($webRoutes) {
                require $webRoutes;
            });
        }

        foreach ($this->app->packages() as $name => $pkg) {
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
        $overridePath = $this->app->basePath('routes/' . basename($routeFile));

        if (file_exists($overridePath)) {
            $routePath = $overridePath;
        }

        if (!file_exists($routePath)) {
            return;
        }

        $prefix = $pkg['components-prefix'] ?? '';
        $app = $prefix ? rtrim($prefix, ':') : 'frontend';

        $router->group(['prefix' => $app, 'app' => $app ?: 'frontend'], function (Router $router) use ($routePath) {
            require $routePath;
        });
    }

    private function shortName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }
}
