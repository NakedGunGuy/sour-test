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

    /**
     * Load config from a package path as defaults (won't override existing keys).
     */
    public function loadPackageConfig(string $file, ?string $namespace = null): void
    {
        if (!file_exists($file)) {
            return;
        }

        $key = $namespace ?? basename($file, '.php');
        $values = require $file;

        if (!isset($this->items[$key])) {
            // No project override at all — use package defaults
            $this->items[$key] = $values;
        } elseif (is_array($this->items[$key]) && is_array($values)) {
            // Deep merge: project values override package defaults
            $this->items[$key] = $this->deepMerge($values, $this->items[$key]);
        }
        // If project has a non-array value, keep it as-is
    }

    private function deepMerge(array $defaults, array $overrides): array
    {
        $result = $defaults;
        foreach ($overrides as $key => $value) {
            if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                $result[$key] = $this->deepMerge($result[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public function all(): array
    {
        return $this->items;
    }
}
