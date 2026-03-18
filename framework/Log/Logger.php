<?php

declare(strict_types=1);

namespace Sauerkraut\Log;

class Logger
{
    private const string FORMAT = "[%s] %s: %s\n";

    public function __construct(private string $logDir)
    {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    private function log(string $level, string $message, array $context): void
    {
        $message = $this->interpolate($message, $context);
        $entry = sprintf(self::FORMAT, date('Y-m-d H:i:s'), $level, $message);
        $file = $this->logDir . '/' . date('Y-m-d') . '.log';

        file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
    }

    private function interpolate(string $message, array $context): string
    {
        foreach ($context as $key => $value) {
            $message = str_replace('{' . $key . '}', $this->stringify($value), $message);
        }

        return $message;
    }

    private function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
