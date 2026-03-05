<?php

declare(strict_types=1);

namespace Sauerkraut\Config;

class Config
{
    private array $items = [];

    public function __construct(string $configPath)
    {
        $this->loadFrom($configPath);
    }

    private function loadFrom(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (glob("{$path}/*.php") as $file) {
            $key = basename($file, '.php');
            $this->items[$key] = require $file;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $target = &$this->items;

        foreach (array_slice($segments, 0, -1) as $segment) {
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }
            $target = &$target[$segment];
        }

        $target[end($segments)] = $value;
    }

    public function all(): array
    {
        return $this->items;
    }
}
