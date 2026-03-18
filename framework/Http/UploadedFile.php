<?php

declare(strict_types=1);

namespace Sauerkraut\Http;

class UploadedFile
{
    private const array DANGEROUS_EXTENSIONS = [
        'php', 'phtml', 'phar', 'php3', 'php4', 'php5',
        'exe', 'bat', 'cmd', 'sh', 'cgi',
    ];

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

    public function detectedMimeType(): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->file($this->tmpPath) ?: 'application/octet-stream';
    }

    public function isImage(): bool
    {
        return str_starts_with($this->detectedMimeType(), 'image/');
    }

    public function isDangerous(): bool
    {
        return in_array($this->extension(), self::DANGEROUS_EXTENSIONS, strict: true);
    }

    public function validateMimeType(array $allowedTypes): bool
    {
        return in_array($this->detectedMimeType(), $allowedTypes, strict: true);
    }

    public function validateMaxSize(int $maxBytes): bool
    {
        return $this->size <= $maxBytes;
    }

    public function store(string $directory, ?string $filename = null): string
    {
        if (!$this->isValid()) {
            throw new \RuntimeException('Cannot store an invalid upload.');
        }

        if ($this->isDangerous()) {
            throw new \RuntimeException("Dangerous file extension: {$this->extension()}");
        }

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename ??= $this->generateFilename();
        $destination = rtrim($directory, '/') . '/' . $filename;

        if (!move_uploaded_file($this->tmpPath, $destination)) {
            throw new \RuntimeException("Failed to move uploaded file to {$destination}");
        }

        return $filename;
    }

    private function generateFilename(): string
    {
        return bin2hex(random_bytes(16)) . '.' . $this->extension();
    }
}
