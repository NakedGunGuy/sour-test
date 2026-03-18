<?php

declare(strict_types=1);

namespace Sauerkraut\Http;

readonly class HttpRequest
{
    public function __construct(
        public string $method,
        public string $url,
        public ?string $body = null,
        public array $headers = [],
    ) {}
}
