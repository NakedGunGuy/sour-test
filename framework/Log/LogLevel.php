<?php

declare(strict_types=1);

namespace Sauerkraut\Log;

enum LogLevel: string
{
    case DEBUG = 'debug';
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';

    public function priority(): int
    {
        return match ($this) {
            self::DEBUG => 0,
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3,
        };
    }

    public function meetsMinimum(self $minimum): bool
    {
        return $this->priority() >= $minimum->priority();
    }
}
