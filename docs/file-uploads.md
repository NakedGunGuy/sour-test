# File Uploads

Secure file upload handling with server-side MIME detection.

## Handling Uploads

```php
use Sauerkraut\Http\UploadedFile;

$fileData = $request->file('avatar');
$file = new UploadedFile($fileData);

// Validate
if (!$file->isValid()) {
    return $this->redirect('/upload?error=invalid');
}

if (!$file->validateMimeType(['image/jpeg', 'image/png', 'image/webp'])) {
    return $this->redirect('/upload?error=type');
}

if (!$file->validateMaxSize(5 * 1024 * 1024)) { // 5MB
    return $this->redirect('/upload?error=size');
}

// Store with random filename
$filename = $file->store($this->app->basePath('storage/uploads'));
// Returns: "a1b2c3d4e5f6...jpg"
```

## Security Features

| Method | Description |
|--------|-------------|
| `detectedMimeType()` | Server-side MIME via `finfo` (not client-supplied) |
| `isDangerous()` | Checks for `.php`, `.exe`, `.sh`, etc. |
| `validateMimeType($types)` | Whitelist allowed MIME types |
| `validateMaxSize($bytes)` | Check file size limit |
| `isImage()` | Shorthand for image MIME check |

The `store()` method automatically:
- Rejects invalid uploads
- Blocks dangerous extensions (`.php`, `.phar`, `.exe`, etc.)
- Generates random filenames (prevents path traversal and overwrites)
- Creates the destination directory if needed

## Properties

| Property | Description |
|----------|-------------|
| `$file->originalName` | Client-supplied filename (untrusted) |
| `$file->mimeType` | Client-supplied MIME type (untrusted) |
| `$file->size` | File size in bytes |
| `$file->error` | PHP upload error code |

Always use `detectedMimeType()` instead of `mimeType` for security decisions.
