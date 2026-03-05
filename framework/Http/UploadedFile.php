<?php

declare(strict_types=1);

namespace Sauerkraut\Http;

class UploadedFile
{
    public readonly string $originalName;
    public readonly string $mimeType;
    public readonly int $size;
    public readonly string $tmpPath;
    public readonly int $error;

    public function __construct(array $file)
    {
        $this->originalName = $file['name'];
        $this->mimeType = $file['type'];
        $this->size = $file['size'];
        $this->tmpPath = $file['tmp_name'];
        $this->error = $file['error'];
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && is_uploaded_file($this->tmpPath);
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->originalName, PATHINFO_EXTENSION));
    }

    public function store(string $directory, ?string $filename = null): string
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename ??= $this->generateFilename();
        $destination = rtrim($directory, '/') . '/' . $filename;

        move_uploaded_file($this->tmpPath, $destination);

        return $filename;
    }

    private function generateFilename(): string
    {
        return bin2hex(random_bytes(16)) . '.' . $this->extension();
    }
}
