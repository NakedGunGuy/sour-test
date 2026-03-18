<?php

declare(strict_types=1);

namespace Sauerkraut\Console\Commands;

use Sauerkraut\Console\Argument;
use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Option;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Signature;

class PublishCommand extends Command
{
    public function signature(): Signature
    {
        return new Signature(
            name: 'publish',
            description: 'Publish files from a sauerkraut package to your project',
            arguments: [
                new Argument('package', 'Package name (e.g. sauerkraut/ui)'),
                new Argument('target', 'Specific target to publish (e.g. components/button)'),
            ],
            options: [
                new Option('all', 'Publish everything from the package'),
                new Option('force', 'Overwrite existing files', 'f'),
            ],
        );
    }

    public function handle(Input $input, Output $output): int
    {
        $packageName = $input->argument('package');

        if (!$packageName) {
            return $this->listPackages($output);
        }

        return $this->publishPackage($packageName, $input, $output);
    }

    private function listPackages(Output $output): int
    {
        $packages = $this->app->packages();

        if (empty($packages)) {
            $output->warn('No sauerkraut packages installed.');
            return 1;
        }

        $output->newLine();
        $output->info('Available packages:');
        $output->newLine();

        foreach (array_keys($packages) as $name) {
            $output->line("  {$name}");
        }

        $output->newLine();
        $output->line('Usage: php sauerkraut publish <package> [target] [--all] [--force]');
        $output->newLine();

        return 0;
    }

    private function publishPackage(string $packageName, Input $input, Output $output): int
    {
        $packages = $this->app->packages();

        if (!isset($packages[$packageName])) {
            $output->error("Package '{$packageName}' not found. Run: composer require {$packageName}");
            return 1;
        }

        $config = $packages[$packageName];
        $installPath = $config['install_path'];
        $publishTo = $this->resolvePublishDir($config);
        $publishable = $this->discoverPublishable($config, $installPath, $publishTo);

        if (empty($publishable)) {
            $output->warn("No publishable files found in {$packageName}.");
            return 1;
        }

        $force = $input->hasOption('force');
        $all = $input->hasOption('all');
        $target = $input->argument('target');

        if (!$all && !$target) {
            return $this->listTargets($packageName, $publishable, $output);
        }

        if ($all) {
            return $this->publishAll($publishable, $force, $output);
        }

        return $this->publishTarget($target, $packageName, $publishable, $force, $output);
    }

    private function resolvePublishDir(array $config): string
    {
        $prefix = $config['components-prefix'] ?? '';
        $app = $prefix ? rtrim($prefix, ':') : 'frontend';

        return $app;
    }

    /** @return array<string, array{source: string, dest: string, label: string}> */
    private function discoverPublishable(array $config, string $installPath, string $publishTo): array
    {
        $basePath = $this->app->basePath();
        $publishable = [];

        $this->discoverComponents($config, $installPath, $basePath, $publishTo, $publishable);
        $this->discoverPages($config, $installPath, $basePath, $publishTo, $publishable);
        $this->discoverCss($config, $installPath, $basePath, $publishTo, $publishable);
        $this->discoverConfig($config, $installPath, $basePath, $publishable);
        $this->discoverRoutes($config, $installPath, $basePath, $publishable);

        return $publishable;
    }

    private function discoverComponents(array $config, string $installPath, string $basePath, string $publishTo, array &$publishable): void
    {
        $componentsPath = $config['components-path'] ?? null;

        if (!$componentsPath || !is_dir($installPath . '/' . $componentsPath)) {
            return;
        }

        $dirs = glob($installPath . '/' . $componentsPath . '/*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $name = basename($dir);
            $publishable['components/' . $name] = [
                'source' => $dir,
                'dest' => $basePath . '/' . $publishTo . '/components/' . $name,
                'label' => "component: {$name}",
            ];
        }
    }

    private function discoverPages(array $config, string $installPath, string $basePath, string $publishTo, array &$publishable): void
    {
        $pagesPath = $config['pages-path'] ?? null;

        if (!$pagesPath || !is_dir($installPath . '/' . $pagesPath)) {
            return;
        }

        $publishable['pages'] = [
            'source' => $installPath . '/' . $pagesPath,
            'dest' => $basePath . '/' . $publishTo . '/pages',
            'label' => 'pages',
        ];
    }

    private function discoverCss(array $config, string $installPath, string $basePath, string $publishTo, array &$publishable): void
    {
        $cssPath = $config['css'] ?? null;

        if (!$cssPath || !file_exists($installPath . '/' . $cssPath)) {
            return;
        }

        $publishable['css'] = [
            'source' => $installPath . '/' . $cssPath,
            'dest' => $basePath . '/' . $publishTo . '/' . basename($cssPath),
            'label' => 'css',
        ];
    }

    private function discoverConfig(array $config, string $installPath, string $basePath, array &$publishable): void
    {
        $configPath = $config['config'] ?? null;

        if (!$configPath || !file_exists($installPath . '/' . $configPath)) {
            return;
        }

        $publishable['config'] = [
            'source' => $installPath . '/' . $configPath,
            'dest' => $basePath . '/config/' . basename($configPath),
            'label' => 'config',
        ];
    }

    private function discoverRoutes(array $config, string $installPath, string $basePath, array &$publishable): void
    {
        $routesPath = $config['routes'] ?? null;

        if (!$routesPath || !file_exists($installPath . '/' . $routesPath)) {
            return;
        }

        $publishable['routes'] = [
            'source' => $installPath . '/' . $routesPath,
            'dest' => $basePath . '/routes/' . basename($routesPath),
            'label' => 'routes',
        ];
    }

    private function listTargets(string $packageName, array $publishable, Output $output): int
    {
        $output->newLine();
        $output->line("Usage: php sauerkraut publish {$packageName} <target>");
        $output->line("       php sauerkraut publish {$packageName} --all");
        $output->newLine();
        $output->line('Options:');
        $output->line('  --force, -f   Overwrite existing files');
        $output->line('  --all         Publish everything');
        $output->newLine();
        $output->line('Available targets:');

        foreach ($publishable as $key => $item) {
            $output->line("  {$key}");
        }

        $output->newLine();

        return 0;
    }

    private function publishAll(array $publishable, bool $force, Output $output): int
    {
        foreach ($publishable as $key => $item) {
            $this->publishItem($key, $item, $force, $output);
        }

        return 0;
    }

    private function publishTarget(string $target, string $packageName, array $publishable, bool $force, Output $output): int
    {
        if (isset($publishable[$target])) {
            $this->publishItem($target, $publishable[$target], $force, $output);
            return 0;
        }

        $componentKey = 'components/' . $target;

        if (isset($publishable[$componentKey])) {
            $this->publishItem($componentKey, $publishable[$componentKey], $force, $output);
            return 0;
        }

        $output->error("Target '{$target}' not found in {$packageName}.");
        return 1;
    }

    private function publishItem(string $key, array $item, bool $force, Output $output): void
    {
        $source = $item['source'];
        $dest = $item['dest'];

        if (is_dir($source)) {
            $this->publishDir($source, $dest, $key, $force, $output);
        } else {
            $this->publishFile($source, $dest, $key, $force, $output);
        }
    }

    private function publishDir(string $source, string $dest, string $label, bool $force, Output $output): void
    {
        if (is_dir($dest) && !$force) {
            $output->warn("  Skipped: {$label} (already exists, use --force to overwrite)");
            return;
        }

        if (is_dir($dest)) {
            $this->removeDirectory($dest);
        }

        mkdir($dest, 0755, true);
        $this->copyDirectory($source, $dest);
        $output->success("  Published: {$label}");
    }

    private function publishFile(string $source, string $dest, string $label, bool $force, Output $output): void
    {
        if (file_exists($dest) && !$force) {
            $output->warn("  Skipped: {$label} (already exists, use --force to overwrite)");
            return;
        }

        $destDir = dirname($dest);

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        copy($source, $dest);
        $output->success("  Published: {$label}");
    }

    private function removeDirectory(string $path): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($path);
    }

    private function copyDirectory(string $source, string $dest): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            $relative = substr($file->getPathname(), strlen($source));
            $relative = ltrim(str_replace('\\', '/', $relative), '/');
            $targetPath = "{$dest}/{$relative}";
            $targetDir = dirname($targetPath);

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            copy($file->getPathname(), $targetPath);
        }
    }
}
