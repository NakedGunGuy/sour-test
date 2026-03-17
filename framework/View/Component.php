<?php

declare(strict_types=1);

namespace Sauerkraut\View;

class Component
{
    private static array $registered = [];
    private static array $groups = [];
    private static array $queuedStyles = [];
    private static array $queuedScripts = [];
    private static array $stack = [];

    public static function register(string $name, string $path, string $group = 'frontend'): void
    {
        static::$registered[$name] = rtrim($path, '/');
        static::$groups[$name] = $group;
    }

    public static function render(string $name, array $props = [], ?string $slot = null): string
    {
        if (str_contains($name, '/')) {
            return static::renderSubComponent($name, $props, $slot);
        }

        $path = static::$registered[$name]
            ?? throw new \RuntimeException("Component [{$name}] is not registered.");

        $basename = static::basename($name);
        $template = "{$path}/{$basename}.php";

        if (!file_exists($template)) {
            throw new \RuntimeException("Component template not found: {$template}");
        }

        static::queueAssets($name, $path, $basename);

        ob_start();
        extract($props);
        include $template;
        return ob_get_clean();
    }

    private static function renderSubComponent(string $name, array $props, ?string $slot): string
    {
        [$parent, $child] = explode('/', $name, 2);

        $parentPath = static::$registered[$parent]
            ?? throw new \RuntimeException("Component [{$parent}] is not registered (parent of [{$name}]).");

        $parentGroup = static::$groups[$parent] ?? 'frontend';
        $parentBasename = static::basename($parent);

        $childDir = "{$parentPath}/{$child}";
        $childTemplate = "{$childDir}/{$child}.php";
        $parentTemplate = "{$parentPath}/{$parentBasename}.php";

        $template = file_exists($childTemplate) ? $childTemplate : $parentTemplate;

        if (!file_exists($template)) {
            throw new \RuntimeException("Component template not found: {$template}");
        }

        static::queueAssets($parent, $parentPath, $parentBasename);
        static::queueAssets($name, $childDir, $child, $parentGroup);

        ob_start();
        extract($props);
        include $template;
        return ob_get_clean();
    }

    public static function open(string $name, array $props = []): void
    {
        static::$stack[] = ['name' => $name, 'props' => $props];
        ob_start();
    }

    public static function close(): void
    {
        if (empty(static::$stack)) {
            throw new \RuntimeException('close() called without a matching open().');
        }

        $slot = ob_get_clean();
        $current = array_pop(static::$stack);
        echo static::render($current['name'], $current['props'], $slot);
    }

    // --- Asset queuing & bundling ---

    private static function queueAssets(string $name, string $path, string $basename, ?string $group = null): void
    {
        $stylesheet = "{$path}/{$basename}.css";
        if (file_exists($stylesheet) && !isset(static::$queuedStyles[$name])) {
            static::$queuedStyles[$name] = $stylesheet;
            if ($group !== null) {
                static::$groups[$name] = $group;
            }
        }

        $script = "{$path}/{$basename}.js";
        if (file_exists($script) && !isset(static::$queuedScripts[$name])) {
            static::$queuedScripts[$name] = $script;
            if ($group !== null) {
                static::$groups[$name] = $group;
            }
        }
    }

    private static function getAssetContent(string $name, string $extension): ?string
    {
        if (str_contains($name, '/')) {
            [$parent, $child] = explode('/', $name, 2);
            $parentPath = static::$registered[$parent] ?? null;
            if ($parentPath === null) {
                return null;
            }
            $file = "{$parentPath}/{$child}/{$child}.{$extension}";
            return file_exists($file) ? file_get_contents($file) : null;
        }

        $path = static::$registered[$name] ?? null;
        if ($path === null) {
            return null;
        }

        $basename = static::basename($name);
        $file = "{$path}/{$basename}.{$extension}";
        return file_exists($file) ? file_get_contents($file) : null;
    }

    private static function collectInlineAssets(?string $group, string $extension): string
    {
        $group ??= View::currentApp();

        if (config('assets', 'link') !== 'inline') {
            return '';
        }

        $queue = $extension === 'css' ? static::$queuedStyles : static::$queuedScripts;
        $output = '';

        foreach (static::$registered as $name => $path) {
            if (static::$groups[$name] !== $group) {
                continue;
            }

            $basename = static::basename($name);
            $file = "{$path}/{$basename}.{$extension}";

            if (file_exists($file)) {
                $output .= "/* {$name} */\n" . file_get_contents($file) . "\n";
            }
        }

        foreach ($queue as $name => $path) {
            if (isset(static::$registered[$name])) {
                continue;
            }
            $output .= "/* {$name} */\n" . file_get_contents($path) . "\n";
        }

        return $output;
    }

    private static function collectAssetTags(?string $group, string $extension): string
    {
        $group ??= View::currentApp();

        if (config('assets', 'link') === 'inline') {
            return '';
        }

        $queue = $extension === 'css' ? static::$queuedStyles : static::$queuedScripts;
        $slugs = [];

        foreach ($queue as $name => $path) {
            if ((static::$groups[$name] ?? 'frontend') !== $group) {
                continue;
            }
            $slugs[] = str_replace('/', '.', $name);
        }

        if (empty($slugs)) {
            return '';
        }

        $names = implode(',', $slugs);
        return $extension === 'css'
            ? '    <link rel="stylesheet" href="/css/components.css?c=' . $names . '">' . "\n"
            : '    <script src="/js/components.js?c=' . $names . '" defer></script>' . "\n";
    }

    // --- Public asset API (delegates to unified helpers) ---

    public static function getStylesheetContent(string $name): ?string
    {
        return static::getAssetContent($name, 'css');
    }

    public static function getScriptContent(string $name): ?string
    {
        return static::getAssetContent($name, 'js');
    }

    public static function inlineStyles(?string $group = null): string
    {
        return static::collectInlineAssets($group, 'css');
    }

    public static function inlineScripts(?string $group = null): string
    {
        return static::collectInlineAssets($group, 'js');
    }

    public static function linkTags(?string $group = null): string
    {
        return static::collectAssetTags($group, 'css');
    }

    public static function scriptTags(?string $group = null): string
    {
        return static::collectAssetTags($group, 'js');
    }

    public static function bundleAssets(string $type, string $names): string
    {
        $output = '';

        foreach (explode(',', $names) as $slug) {
            $name = str_replace('.', '/', $slug);
            $content = static::getAssetContent($name, $type);

            if ($content !== null) {
                $output .= "/* {$name} */\n{$content}\n";
            }
        }

        return $output;
    }

    public static function reset(): void
    {
        static::$queuedStyles = [];
        static::$queuedScripts = [];
        static::$stack = [];
    }

    // --- Helpers ---

    private static function basename(string $name): string
    {
        if (str_contains($name, ':')) {
            return explode(':', $name, 2)[1];
        }
        return basename($name);
    }
}
