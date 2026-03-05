<?php

namespace Sauerkraut\View;

class Component
{
    private static array $registered = [];
    private static array $groups = [];
    private static array $queuedStyles = [];
    private static array $queuedScripts = [];
    private static array $stack = [];

    public static function register(string $name, string $path, string $group = 'front'): void
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

        $basename = basename($name);
        $template = "{$path}/{$basename}.php";
        $stylesheet = "{$path}/{$basename}.css";

        if (!file_exists($template)) {
            throw new \RuntimeException("Component template not found: {$template}");
        }

        if (file_exists($stylesheet) && !isset(static::$queuedStyles[$name])) {
            static::$queuedStyles[$name] = $stylesheet;
        }

        $script = "{$path}/{$basename}.js";

        if (file_exists($script) && !isset(static::$queuedScripts[$name])) {
            static::$queuedScripts[$name] = $script;
        }

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

        $childDir = "{$parentPath}/{$child}";
        $parentTemplate = "{$parentPath}/{$parent}.php";
        $childTemplate = "{$childDir}/{$child}.php";
        $parentStylesheet = "{$parentPath}/{$parent}.css";
        $childStylesheet = "{$childDir}/{$child}.css";
        $parentScript = "{$parentPath}/{$parent}.js";
        $childScript = "{$childDir}/{$child}.js";

        $template = file_exists($childTemplate) ? $childTemplate : $parentTemplate;

        if (!file_exists($template)) {
            throw new \RuntimeException("Component template not found: {$template}");
        }

        if (file_exists($parentStylesheet) && !isset(static::$queuedStyles[$parent])) {
            static::$queuedStyles[$parent] = $parentStylesheet;
        }

        if (file_exists($childStylesheet) && !isset(static::$queuedStyles[$name])) {
            static::$queuedStyles[$name] = $childStylesheet;
        }

        if (file_exists($parentScript) && !isset(static::$queuedScripts[$parent])) {
            static::$queuedScripts[$parent] = $parentScript;
        }

        if (file_exists($childScript) && !isset(static::$queuedScripts[$name])) {
            static::$queuedScripts[$name] = $childScript;
        }

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

    public static function getStylesheetContent(string $name): ?string
    {
        if (str_contains($name, '/')) {
            [$parent, $child] = explode('/', $name, 2);
            $parentPath = static::$registered[$parent] ?? null;

            if ($parentPath === null) {
                return null;
            }

            $stylesheet = "{$parentPath}/{$child}/{$child}.css";

            return file_exists($stylesheet) ? file_get_contents($stylesheet) : null;
        }

        $path = static::$registered[$name] ?? null;

        if ($path === null) {
            return null;
        }

        $stylesheet = "{$path}/{$name}.css";

        return file_exists($stylesheet) ? file_get_contents($stylesheet) : null;
    }

    /**
     * Returns all registered component CSS for a group (inline mode).
     * Returns empty string in link mode.
     */
    public static function inlineStyles(string $group = 'front'): string
    {
        if (config('app.css', 'link') !== 'inline') {
            return '';
        }

        $output = '';

        foreach (static::$registered as $name => $path) {
            if (static::$groups[$name] !== $group) {
                continue;
            }

            $basename = basename($name);
            $stylesheet = "{$path}/{$basename}.css";

            if (file_exists($stylesheet)) {
                $css = file_get_contents($stylesheet);
                $output .= "/* {$name} */\n{$css}\n";
            }
        }

        foreach (static::$queuedStyles as $name => $path) {
            if (isset(static::$registered[$name])) {
                continue;
            }

            $css = file_get_contents($path);
            $output .= "/* {$name} */\n{$css}\n";
        }

        return $output;
    }

    /**
     * Returns <link> tags for queued components (link mode).
     * Returns empty string in inline mode.
     */
    public static function linkTags(): string
    {
        if (config('app.css', 'link') === 'inline') {
            return '';
        }

        if (empty(static::$queuedStyles)) {
            return '';
        }

        $slugs = array_map(
            fn($name) => str_replace('/', '.', $name),
            array_keys(static::$queuedStyles)
        );

        return '    <link rel="stylesheet" href="/css/components.css?c=' . implode(',', $slugs) . '">' . "\n";
    }

    public static function getScriptContent(string $name): ?string
    {
        if (str_contains($name, '/')) {
            [$parent, $child] = explode('/', $name, 2);
            $parentPath = static::$registered[$parent] ?? null;

            if ($parentPath === null) {
                return null;
            }

            $script = "{$parentPath}/{$child}/{$child}.js";

            return file_exists($script) ? file_get_contents($script) : null;
        }

        $path = static::$registered[$name] ?? null;

        if ($path === null) {
            return null;
        }

        $script = "{$path}/{$name}.js";

        return file_exists($script) ? file_get_contents($script) : null;
    }

    public static function inlineScripts(string $group = 'front'): string
    {
        if (config('app.css', 'link') !== 'inline') {
            return '';
        }

        $output = '';

        foreach (static::$registered as $name => $path) {
            if (static::$groups[$name] !== $group) {
                continue;
            }

            $script = "{$path}/{$name}.js";

            if (file_exists($script)) {
                $js = file_get_contents($script);
                $output .= "/* {$name} */\n{$js}\n";
            }
        }

        foreach (static::$queuedScripts as $name => $path) {
            if (isset(static::$registered[$name])) {
                continue;
            }

            $js = file_get_contents($path);
            $output .= "/* {$name} */\n{$js}\n";
        }

        return $output;
    }

    public static function scriptTags(): string
    {
        if (config('app.css', 'link') === 'inline') {
            return '';
        }

        if (empty(static::$queuedScripts)) {
            return '';
        }

        $slugs = array_map(
            fn($name) => str_replace('/', '.', $name),
            array_keys(static::$queuedScripts)
        );

        return '    <script src="/js/components.js?c=' . implode(',', $slugs) . '" defer></script>' . "\n";
    }

    public static function reset(): void
    {
        static::$queuedStyles = [];
        static::$queuedScripts = [];
        static::$stack = [];
    }
}
