<?php

namespace Sauerkraut\View;

class View
{
    private static string $basePath;
    private static string $currentApp = 'frontend';

    /** @var array<string, string[]> Pages directories per app (last registered wins = highest priority) */
    private static array $pagesDirs = [];

    /** @var array<string, string[]> CSS files per app (last registered wins = highest priority) */
    private static array $cssFiles = [];

    public static function setBasePath(string $path): void
    {
        static::$basePath = rtrim($path, '/');
    }

    public static function currentApp(): string
    {
        return static::$currentApp;
    }

    public static function setCurrentApp(string $app): void
    {
        static::$currentApp = $app;
    }

    /**
     * Register a pages directory for an app. Later registrations take priority.
     */
    public static function registerPagesDir(string $app, string $dir): void
    {
        static::$pagesDirs[$app][] = $dir;
    }

    /**
     * Register a CSS file for an app. Later registrations take priority.
     */
    public static function registerCssFile(string $app, string $file): void
    {
        static::$cssFiles[$app][] = $file;
    }

    /**
     * Get the CSS content for the current or specified app.
     */
    public static function appCss(string $app): string
    {
        $files = static::$cssFiles[$app] ?? [];
        if (empty($files)) {
            return '';
        }

        // Last registered file wins (project override > vendor)
        $file = end($files);
        if (!file_exists($file)) {
            return '';
        }

        return file_get_contents($file);
    }

    public static function render(string $page, array $data = [], ?string $layout = null, ?string $app = null): string
    {
        if ($app !== null) {
            static::$currentApp = $app;
        }

        $currentApp = static::$currentApp;

        $pagePath = static::resolvePage($page, $currentApp);
        if (!$pagePath) {
            throw new \RuntimeException("Page not found: {$page} (app: {$currentApp})");
        }

        // Render the page content first so components can queue their styles
        ob_start();
        extract($data);
        include $pagePath;
        $content = ob_get_clean();

        // Now render the layout with collected styles and content
        $layoutFile = $layout ?? 'layout';
        $layoutPath = static::resolvePage($layoutFile, $currentApp);
        if (!$layoutPath) {
            throw new \RuntimeException("Layout not found: {$layoutFile} (app: {$currentApp})");
        }

        ob_start();
        include $layoutPath;
        return ob_get_clean();
    }

    public static function partial(string $page, array $data = [], ?string $app = null): string
    {
        if ($app !== null) {
            static::$currentApp = $app;
        }

        $currentApp = static::$currentApp;

        $pagePath = static::resolvePage($page, $currentApp);
        if (!$pagePath) {
            throw new \RuntimeException("Page not found: {$page} (app: {$currentApp})");
        }

        ob_start();
        extract($data);
        include $pagePath;
        $content = ob_get_clean();

        $inlineCss = Component::inlineStyles();
        $inlineJs = Component::inlineScripts();
        $styles = $inlineCss !== '' ? "<style>{$inlineCss}</style>" : Component::linkTags();
        $scripts = $inlineJs !== '' ? "<script>{$inlineJs}</script>" : Component::scriptTags();
        $head = $styles . $scripts;

        if ($head === '') {
            return $content;
        }

        return "<html lang=\"en\"><head>{$head}</head><body>{$content}</body></html>";
    }

    /**
     * Resolve a page file path. Checks registered dirs in reverse order (last = highest priority),
     * then falls back to the project-level {app}/pages/ directory.
     */
    private static function resolvePage(string $page, string $app): ?string
    {
        $dirs = static::$pagesDirs[$app] ?? [];

        // Check in reverse order — last registered has highest priority (project overrides)
        for ($i = count($dirs) - 1; $i >= 0; $i--) {
            $path = $dirs[$i] . '/' . $page . '.php';
            if (file_exists($path)) {
                return $path;
            }
        }

        // Fallback: project-level {basePath}/{app}/pages/
        $projectPath = static::$basePath . '/' . $app . '/pages/' . $page . '.php';
        if (file_exists($projectPath)) {
            return $projectPath;
        }

        return null;
    }
}
