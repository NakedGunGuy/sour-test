<?php

declare(strict_types=1);

namespace Sauerkraut\Console;

class Input
{
    private string $command;

    /** @var array<string, string|null> */
    private array $arguments = [];

    /** @var array<string, string|bool> */
    private array $options = [];

    public function __construct(Signature $signature, array $argv)
    {
        $this->command = $argv[1] ?? '';
        $tokens = array_slice($argv, 2);
        $this->parse($signature, $tokens);
    }

    public function command(): string
    {
        return $this->command;
    }

    public function argument(string $name): ?string
    {
        return $this->arguments[$name] ?? null;
    }

    public function option(string $name): string|bool|null
    {
        return $this->options[$name] ?? null;
    }

    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    private function parse(Signature $signature, array $tokens): void
    {
        $this->parseOptions($signature, $tokens);
        $this->parseArguments($signature, $tokens);
    }

    private function parseOptions(Signature $signature, array &$tokens): void
    {
        $shortcuts = $this->buildShortcutMap($signature);
        $remaining = [];

        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];

            if (str_starts_with($token, '--')) {
                $this->parseLongOption($signature, $token, $tokens, $i);
                continue;
            }

            if (str_starts_with($token, '-') && strlen($token) > 1) {
                $this->parseShortOption($signature, $shortcuts, $token, $tokens, $i);
                continue;
            }

            $remaining[] = $token;
        }

        $tokens = $remaining;
        $this->applyOptionDefaults($signature);
    }

    private function parseLongOption(Signature $signature, string $token, array $tokens, int &$i): void
    {
        $name = substr($token, 2);
        $option = $this->findOption($signature, $name);

        if (!$option) {
            return;
        }

        if ($option->acceptsValue && isset($tokens[$i + 1]) && !str_starts_with($tokens[$i + 1], '-')) {
            $this->options[$option->name] = $tokens[++$i];
        } else {
            $this->options[$option->name] = $option->acceptsValue ? $option->default : true;
        }
    }

    private function parseShortOption(Signature $signature, array $shortcuts, string $token, array $tokens, int &$i): void
    {
        $shortcut = substr($token, 1);
        $name = $shortcuts[$shortcut] ?? null;

        if (!$name) {
            return;
        }

        $option = $this->findOption($signature, $name);

        if ($option?->acceptsValue && isset($tokens[$i + 1]) && !str_starts_with($tokens[$i + 1], '-')) {
            $this->options[$name] = $tokens[++$i];
        } else {
            $this->options[$name] = true;
        }
    }

    private function parseArguments(Signature $signature, array $tokens): void
    {
        foreach ($signature->arguments as $index => $argument) {
            $this->arguments[$argument->name] = $tokens[$index] ?? $argument->default;
        }
    }

    /** @return array<string, string> shortcut => option name */
    private function buildShortcutMap(Signature $signature): array
    {
        $map = [];

        foreach ($signature->options as $option) {
            if ($option->shortcut !== null) {
                $map[$option->shortcut] = $option->name;
            }
        }

        return $map;
    }

    private function findOption(Signature $signature, string $name): ?Option
    {
        foreach ($signature->options as $option) {
            if ($option->name === $name) {
                return $option;
            }
        }

        return null;
    }

    private function applyOptionDefaults(Signature $signature): void
    {
        foreach ($signature->options as $option) {
            if (!isset($this->options[$option->name]) && $option->default !== null) {
                $this->options[$option->name] = $option->default;
            }
        }
    }
}
