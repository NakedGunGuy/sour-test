<?php

namespace Sauerkraut\View;

class View
{
    private static string $basePath;

    public static function setBasePath(string $path): void
    {
        static::$basePath = rtrim($path, '/');
    }

    public static function render(string $page, array $data = [], ?string $layout = null): string
    {
        $pagePath = static::$basePath . "/pages/{$page}.php";

        if (!file_exists($pagePath)) {
            throw new \RuntimeException("Page not found: {$pagePath}");
        }

        // Render the page content first so components can queue their styles
        ob_start();
        extract($data);
        include $pagePath;
        $content = ob_get_clean();

        // Now render the layout with collected styles and content
        $layoutFile = $layout ?? 'layout';
        $layoutPath = static::$basePath . "/pages/{$layoutFile}.php";

        ob_start();
        include $layoutPath;
        return ob_get_clean();
    }

    public static function partial(string $page, array $data = []): string
    {
        $pagePath = static::$basePath . "/pages/{$page}.php";

        if (!file_exists($pagePath)) {
            throw new \RuntimeException("Page not found: {$pagePath}");
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

        return "<html><head>{$head}</head><body>{$content}</body></html>";
    }
}
