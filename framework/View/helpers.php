<?php

declare(strict_types=1);

use Sauerkraut\View\AppContext;
use Sauerkraut\View\Component;

function component(string $name, array $props = [], ?string $slot = null): string
{
    return Component::render($name, $props, $slot);
}

function open(string $name, array $props = []): void
{
    Component::open($name, $props);
}

function close(): void
{
    Component::close();
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function config(string $key, mixed $default = null): mixed
{
    $app = AppContext::get();

    // If the key already targets a specific config file (e.g. "app.debug", "cms.tables"),
    // resolve it directly — no fallback chain.
    if (str_contains($key, '.')) {
        return $app->config($key, $default);
    }

    // Short key (e.g. "assets", "theme") — check current app config first, then app.* default.
    $currentApp = \Sauerkraut\View\View::currentApp();
    if ($currentApp) {
        $appValue = $app->config("{$currentApp}.{$key}");
        if ($appValue !== null) {
            return $appValue;
        }
    }

    return $app->config("app.{$key}", $default);
}

function route(string $name, array $params = []): string
{
    return AppContext::get()->router()->url($name, $params);
}

function url(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}

function csrf_token(): string
{
    return \App\Middleware\Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function method_field(string $method): string
{
    return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
}

function db(): \Sauerkraut\Database\Connection
{
    return AppContext::get()->db();
}

function theme_css(string $file): string
{
    $theme = config('theme', 'theme');
    $path = AppContext::get()->basePath($theme . '/' . $file);

    if (!file_exists($path)) {
        return '';
    }

    return file_get_contents($path);
}

function cms_css(): string
{
    return \Sauerkraut\View\View::appCss('cms');
}

function env(string $key, ?string $default = null): ?string
{
    return \Sauerkraut\Config\Env::get($key, $default);
}

function auth(): ?array
{
    return \Sauerkraut\Auth\Auth::user(db());
}

function auth_check(): bool
{
    return \Sauerkraut\Auth\Auth::check();
}

function auth_id(): ?int
{
    return \Sauerkraut\Auth\Auth::id();
}
