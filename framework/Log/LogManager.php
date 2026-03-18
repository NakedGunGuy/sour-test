<?php

declare(strict_types=1);

namespace Sauerkraut\Log;

class LogManager
{
    /** @var array<string, Logger> */
    private array $loggers = [];

    /**
     * @param array<string, array{path?: string, level?: string}> $channels
     */
    public function __construct(
        private string $basePath,
        private array $channels,
        private string $defaultChannel = 'app',
    ) {}

    public function channel(string $name): Logger
    {
        if (isset($this->loggers[$name])) {
            return $this->loggers[$name];
        }

        $config = $this->channels[$name] ?? [];
        $path = $config['path'] ?? $this->basePath . '/storage/logs';
        $level = $config['level'] ?? 'debug';

        $this->loggers[$name] = new Logger($name, $path, $level);

        return $this->loggers[$name];
    }

    public function default(): Logger
    {
        return $this->channel($this->defaultChannel);
    }

    public static function fromConfig(string $basePath, array $config): self
    {
        $channels = [];

        foreach ($config['channels'] ?? [] as $name => $channelConfig) {
            $path = $channelConfig['path'] ?? null;

            if ($path && !str_starts_with($path, '/')) {
                $path = $basePath . '/' . $path;
            }

            $channels[$name] = [
                'path' => $path ?? $basePath . '/storage/logs',
                'level' => $channelConfig['level'] ?? 'debug',
            ];
        }

        return new self(
            $basePath,
            $channels,
            $config['default'] ?? 'app',
        );
    }
}
