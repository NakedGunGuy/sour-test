<?php

declare(strict_types=1);

namespace Sauerkraut\Console\Commands;

use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Signature;

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

        $router = $this->app->buildRouter();
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
                implode(', ', array_map(fn (string $class) => $this->shortName($class), $route->middleware())),
                $route->app(),
            ];
        }

        $output->table($headers, $rows);
        $output->newLine();

        return 0;
    }

    private function shortName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }
}
