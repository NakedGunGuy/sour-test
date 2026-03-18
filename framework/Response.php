<?php

declare(strict_types=1);

namespace Sauerkraut;

class Response
{
    private string $body;
    private int $status;
    private array $headers;

    public function __construct(string $body = '', int $status = 200, array $headers = [])
    {
        $this->body = $body;
        $this->status = $status;
        $this->headers = $headers;
    }

    public static function html(string $body, int $status = 200): static
    {
        return new static($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function json(mixed $data, int $status = 200): static
    {
        return new static(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    public static function css(string $body, int $status = 200): static
    {
        return new static($body, $status, ['Content-Type' => 'text/css; charset=UTF-8']);
    }

    public static function js(string $body, int $status = 200): static
    {
        return new static($body, $status, ['Content-Type' => 'application/javascript; charset=UTF-8']);
    }

    public static function redirect(string $url, int $status = 302): static
    {
        return new static('', $status, ['Location' => $url]);
    }

    public static function empty(int $status = 204): static
    {
        return new static('', $status);
    }

    public function withHeader(string $name, string $value): static
    {
        if (preg_match('/[\r\n]/', $name . $value)) {
            throw new \InvalidArgumentException('Header values must not contain newlines.');
        }

        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    public function withStatus(int $status): static
    {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    public function withCookie(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = true,
        bool $httpOnly = true,
        string $sameSite = 'Lax',
    ): static {
        $clone = clone $this;
        $clone->headers["Set-Cookie:{$name}"] = sprintf(
            '%s=%s; Path=%s; Expires=%s; SameSite=%s%s%s%s',
            $name,
            urlencode($value),
            $path,
            $expires ? gmdate('D, d M Y H:i:s T', $expires) : '0',
            $sameSite,
            $domain ? "; Domain={$domain}" : '',
            $secure ? '; Secure' : '',
            $httpOnly ? '; HttpOnly' : '',
        );
        return $clone;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            // Handle Set-Cookie headers (keyed with Set-Cookie:name)
            if (str_starts_with($name, 'Set-Cookie:')) {
                header("Set-Cookie: {$value}", false);
            } else {
                header("{$name}: {$value}");
            }
        }

        echo $this->body;
    }
}
