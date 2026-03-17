<?php

declare(strict_types=1);

namespace Sauerkraut\Console;

class Output
{
    private const RESET = "\033[0m";
    private const GREEN = "\033[32m";
    private const RED = "\033[31m";
    private const YELLOW = "\033[33m";
    private const CYAN = "\033[36m";

    public function line(string $text = ''): void
    {
        echo $text . PHP_EOL;
    }

    public function info(string $text): void
    {
        echo self::CYAN . $text . self::RESET . PHP_EOL;
    }

    public function success(string $text): void
    {
        echo self::GREEN . $text . self::RESET . PHP_EOL;
    }

    public function error(string $text): void
    {
        echo self::RED . $text . self::RESET . PHP_EOL;
    }

    public function warn(string $text): void
    {
        echo self::YELLOW . $text . self::RESET . PHP_EOL;
    }

    public function newLine(int $count = 1): void
    {
        echo str_repeat(PHP_EOL, $count);
    }

    /** @param string[] $headers */
    /** @param array<int, string[]> $rows */
    public function table(array $headers, array $rows): void
    {
        $widths = $this->calculateColumnWidths($headers, $rows);
        $separator = $this->buildSeparator($widths);

        $this->line($separator);
        $this->line($this->buildRow($headers, $widths));
        $this->line($separator);

        foreach ($rows as $row) {
            $this->line($this->buildRow($row, $widths));
        }

        $this->line($separator);
    }

    /** @return int[] */
    private function calculateColumnWidths(array $headers, array $rows): array
    {
        $widths = array_map('strlen', $headers);

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i] ?? 0, strlen($cell));
            }
        }

        return $widths;
    }

    private function buildSeparator(array $widths): string
    {
        $parts = array_map(fn (int $w) => str_repeat('-', $w + 2), $widths);

        return '+' . implode('+', $parts) . '+';
    }

    private function buildRow(array $cells, array $widths): string
    {
        $parts = [];

        foreach ($cells as $i => $cell) {
            $parts[] = ' ' . str_pad($cell, $widths[$i]) . ' ';
        }

        return '|' . implode('|', $parts) . '|';
    }
}
