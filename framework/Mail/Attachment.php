<?php

declare(strict_types=1);

namespace Sauerkraut\Mail;

readonly class Attachment
{
    public function __construct(
        public string $path,
        public string $name,
        public string $mimeType = 'application/octet-stream',
    ) {}

    public static function fromPath(string $path, ?string $name = null): self
    {
        return new self(
            $path,
            $name ?? basename($path),
            mime_content_type($path) ?: 'application/octet-stream',
        );
    }
}
