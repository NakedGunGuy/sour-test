<?php

declare(strict_types=1);

namespace Sauerkraut\Http;

readonly class HttpResponse
{
    private const int STATUS_OK_MIN = 200;
    private const int STATUS_REDIRECT_MIN = 300;
    private const int STATUS_CLIENT_ERROR_MIN = 400;
    private const int STATUS_SERVER_ERROR_MIN = 500;

    public function __construct(
        private int $status,
        private string $body,
        private array $headers,
    ) {}

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function json(): mixed
    {
        return json_decode($this->body, true);
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $name): ?string
    {
        $lower = strtolower($name);

        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $lower) {
                return $value;
            }
        }

        return null;
    }

    public function isOk(): bool
    {
        return $this->status >= self::STATUS_OK_MIN && $this->status < self::STATUS_REDIRECT_MIN;
    }

    public function isRedirect(): bool
    {
        return $this->status >= self::STATUS_REDIRECT_MIN && $this->status < self::STATUS_CLIENT_ERROR_MIN;
    }

    public function isClientError(): bool
    {
        return $this->status >= self::STATUS_CLIENT_ERROR_MIN && $this->status < self::STATUS_SERVER_ERROR_MIN;
    }

    public function isServerError(): bool
    {
        return $this->status >= self::STATUS_SERVER_ERROR_MIN;
    }
}
