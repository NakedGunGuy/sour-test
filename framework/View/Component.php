<?php

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

        // For namespaced components like "cms:button", the filename is "button"
        $basename = str_contains($name, ':') ? explode(':', $name, 2)[1] : basename($name);
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

        $parentGroup = static::$groups[$parent] ?? 'frontend';
        $parentBasename = str_contains($parent, ':') ? explode(':', $parent, 2)[1] : $parent;

        $childDir = "{$parentPath}/{$child}";
        $parentTemplate = "{$parentPath}/{$parentBasename}.php";
        $childTemplate = "{$childDir}/{$child}.php";
        $parentStylesheet = "{$parentPath}/{$parentBasename}.css";
        $childStylesheet = "{$childDir}/{$child}.css";
        $parentScript = "{$parentPath}/{$parentBasename}.js";
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
            static::$groups[$name] = $parentGroup;
        }

        if (file_exists($parentScript) && !isset(static::$queuedScripts[$parent])) {
            static::$queuedScripts[$parent] = $parentScript;
        }

        if (file_exists($childScript) && !isset(static::$queuedScripts[$name])) {
            static::$queuedScripts[$name] = $childScript;
            static::$groups[$name] = $parentGroup;
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

        $basename = str_contains($name, ':') ? explode(':', $name, 2)[1] : $name;
        $stylesheet = "{$path}/{$basename}.css";

        return file_exists($stylesheet) ? file_get_contents($stylesheet) : null;
    }

    /**
     * Returns all registered component CSS for a group (inline mode).
     * Returns empty string in link mode.
     */
    public static function inlineStyles(?string $group = null): string
    {
        $group ??= View::currentApp();

        if (config('assets', 'link') !== 'inline') {
            return '';
        }

        $output = '';

        foreach (static::$registered as $name => $path) {
            if (static::$groups[$name] !== $group) {
                continue;
            }

            $basename = str_contains($name, ':') ? explode(':', $name, 2)[1] : basename($name);
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
    public static function linkTags(?string $group = null): string
    {
        $group ??= View::currentApp();

        if (config('assets', 'link') === 'inline') {
            return '';
        }

        $slugs = [];
        foreach (static::$queuedStyles as $name => $path) {
            if ((static::$groups[$name] ?? 'frontend') !== $group) {
                continue;
            }
            $slugs[] = str_replace('/', '.', $name);
        }

        if (empty($slugs)) {
            return '';
        }

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

        $basename = str_contains($name, ':') ? explode(':', $name, 2)[1] : $name;
        $script = "{$path}/{$basename}.js";

        return file_exists($script) ? file_get_contents($script) : null;
    }

    public static function inlineScripts(?string $group = null): string
    {
        $group ??= View::currentApp();

        if (config('assets', 'link') !== 'inline') {
            return '';
        }

        $output = '';

        foreach (static::$registered as $name => $path) {
            if (static::$groups[$name] !== $group) {
                continue;
            }

            $basename = str_contains($name, ':') ? explode(':', $name, 2)[1] : $name;
            $script = "{$path}/{$basename}.js";

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

    public static function scriptTags(?string $group = null): string
    {
        $group ??= View::currentApp();

        if (config('assets', 'link') === 'inline') {
            return '';
        }

        $slugs = [];
        foreach (static::$queuedScripts as $name => $path) {
            if ((static::$groups[$name] ?? 'frontend') !== $group) {
                continue;
            }
            $slugs[] = str_replace('/', '.', $name);
        }

        if (empty($slugs)) {
            return '';
        }

        return '    <script src="/js/components.js?c=' . implode(',', $slugs) . '" defer></script>' . "\n";
    }

    public static function bundleAssets(string $type, string $names): string
    {
        $output = '';

        foreach (explode(',', $names) as $slug) {
            $name = str_replace('.', '/', $slug);
            $content = $type === 'js'
                ? static::getScriptContent($name)
                : static::getStylesheetContent($name);

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
}
