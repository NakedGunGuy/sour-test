<?php

use Sauerkraut\View\Component;
use Sauerkraut\App;

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
    return App::getInstance()->config($key, $default);
}

function route(string $name, array $params = []): string
{
    return App::getInstance()->router()->url($name, $params);
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
    return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

function method_field(string $method): string
{
    return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
}
