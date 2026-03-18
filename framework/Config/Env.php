<?php

declare(strict_types=1);

namespace Sauerkraut\Config;

class Env
{
    private const int NONCE_BYTES = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
    private const int KEY_BYTES = SODIUM_CRYPTO_SECRETBOX_KEYBYTES;

    /** @var array<string, string> */
    private static array $variables = [];

    public static function load(string $basePath): void
    {
        $envFile = $basePath . '/.env';
        $encryptedFile = $basePath . '/.env.encrypted';

        if (file_exists($envFile)) {
            self::$variables = self::parse(file_get_contents($envFile));
            self::applyToEnvironment();
            return;
        }

        if (file_exists($encryptedFile)) {
            $key = self::resolveKey($basePath);

            if ($key === null) {
                throw new \RuntimeException(
                    'Found .env.encrypted but no decryption key. '
                    . 'Set ENV_KEY environment variable or create .env.key file.',
                );
            }

            $encrypted = file_get_contents($encryptedFile);
            $decrypted = self::decrypt($encrypted, $key);
            self::$variables = self::parse($decrypted);
            self::applyToEnvironment();
            return;
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::$variables[$key] ?? $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }

    /** @return array<string, string> */
    public static function all(): array
    {
        return self::$variables;
    }

    public static function encrypt(string $content, string $key): string
    {
        $keyBytes = self::decodeKey($key);
        $nonce = random_bytes(self::NONCE_BYTES);
        $ciphertext = sodium_crypto_secretbox($content, $nonce, $keyBytes);

        return base64_encode($nonce . $ciphertext);
    }

    public static function decrypt(string $encrypted, string $key): string
    {
        $keyBytes = self::decodeKey($key);
        $decoded = base64_decode($encrypted, strict: true);

        if ($decoded === false || strlen($decoded) < self::NONCE_BYTES) {
            throw new \RuntimeException('Invalid encrypted data.');
        }

        $nonce = substr($decoded, 0, self::NONCE_BYTES);
        $ciphertext = substr($decoded, self::NONCE_BYTES);
        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $keyBytes);

        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed. Is the key correct?');
        }

        return $plaintext;
    }

    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(self::KEY_BYTES));
    }

    /** @return array<string, string> */
    public static function parse(string $content): array
    {
        $variables = [];

        foreach (explode("\n", $content) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $separatorPos = strpos($line, '=');

            if ($separatorPos === false) {
                continue;
            }

            $name = trim(substr($line, 0, $separatorPos));
            $value = trim(substr($line, $separatorPos + 1));
            $value = self::unquote($value);

            $variables[$name] = $value;
        }

        return $variables;
    }

    private static function resolveKey(string $basePath): ?string
    {
        $serverKey = $_ENV['ENV_KEY'] ?? $_SERVER['ENV_KEY'] ?? getenv('ENV_KEY');

        if ($serverKey !== false && $serverKey !== '') {
            return $serverKey;
        }

        $keyFile = $basePath . '/.env.key';

        if (file_exists($keyFile)) {
            return trim(file_get_contents($keyFile));
        }

        return null;
    }

    private static function decodeKey(string $key): string
    {
        if (str_starts_with($key, 'base64:')) {
            $key = substr($key, 7);
        }

        $decoded = base64_decode($key, strict: true);

        if ($decoded === false || strlen($decoded) !== self::KEY_BYTES) {
            throw new \RuntimeException('Invalid encryption key.');
        }

        return $decoded;
    }

    private static function unquote(string $value): string
    {
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }

    private static function applyToEnvironment(): void
    {
        foreach (self::$variables as $name => $value) {
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
