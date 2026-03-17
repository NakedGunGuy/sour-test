<?php

declare(strict_types=1);

namespace Sauerkraut\Console;

class ScheduleLock
{
    public function __construct(private string $lockDir)
    {
        if (!is_dir($this->lockDir)) {
            mkdir($this->lockDir, 0755, true);
        }
    }

    public function acquire(string $commandName, int $expiresAfterMinutes): bool
    {
        $lockFile = $this->lockPath($commandName);

        if (file_exists($lockFile) && !$this->isExpired($lockFile, $expiresAfterMinutes)) {
            return false;
        }

        file_put_contents($lockFile, (string) getmypid());

        return true;
    }

    public function release(string $commandName): void
    {
        $lockFile = $this->lockPath($commandName);

        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    }

    private function lockPath(string $commandName): string
    {
        $safe = str_replace([':', '/', '\\'], '-', $commandName);

        return $this->lockDir . '/' . $safe . '.lock';
    }

    private function isExpired(string $lockFile, int $expiresAfterMinutes): bool
    {
        $modified = filemtime($lockFile);

        if ($modified === false) {
            return true;
        }

        $expiresAt = $modified + ($expiresAfterMinutes * 60);

        return time() > $expiresAt;
    }
}
