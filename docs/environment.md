# Environment & Config

## Configuration Files

Config files live in `config/` and return PHP arrays:

```php
// config/app.php
return [
    'name' => 'My App',
    'debug' => true,
    'timezone' => 'UTC',
];
```

Access with dot notation:
```php
$this->app->config('app.name');         // 'My App'
$this->app->config('database.default'); // 'sqlite'
```

In templates:
```php
<?= config('app.name') ?>
```

## Encrypted Environment Variables

Sauerkraut uses sodium-encrypted `.env` files instead of plaintext.

### Workflow

```bash
# 1. Generate encryption key (once)
php sauerkraut env:key
# Creates .env.key (keep secret, never commit)

# 2. Create your .env file
echo "APP_KEY=my-secret" > .env

# 3. Encrypt it
php sauerkraut env:encrypt
# Creates .env.encrypted (safe to commit)

# 4. To edit, decrypt first
php sauerkraut env:decrypt
# Recreates .env from .env.encrypted
```

### Production Deployment

Deploy `.env.encrypted` (from git). Set the decryption key as a server environment variable:

```bash
export ENV_KEY=base64:your-key-here
```

No `.env` file needed on the server — the framework decrypts `.env.encrypted` at boot using `ENV_KEY`.

### Accessing Variables

```php
env('APP_KEY');                    // value or null
env('DB_HOST', '127.0.0.1');     // with default
```

### What Gets Gitignored

```
.env          # plaintext — local only
.env.key      # decryption key — never commit
```

`.env.encrypted` is safe to commit.

### .env Format

```
APP_NAME=Sauerkraut
APP_DEBUG=true
DB_HOST="127.0.0.1"
SECRET='s3cret'

# Comments are ignored
EMPTY_VALUE=
DSN=mysql:host=localhost;dbname=test
```

Supports: comments (`#`), quoted values, empty values, values containing `=`.
