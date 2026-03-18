<?php

declare(strict_types=1);

namespace Sauerkraut\Auth;

use Sauerkraut\Database\Connection;
use Sauerkraut\Http\Session;

class Auth
{
    private const string SESSION_KEY = '_auth_user_id';
    private const string TABLE = 'users';
    private const int MAX_LOGIN_ATTEMPTS = 5;
    private const int LOCKOUT_SECONDS = 300;

    private static ?array $cachedUser = null;

    public static function attempt(Connection $db, string $email, string $password): bool
    {
        if (self::isLockedOut($email)) {
            return false;
        }

        $user = $db->queryOne(
            'SELECT * FROM "' . self::TABLE . '" WHERE "email" = ?',
            [$email],
        );

        if (!$user || !password_verify($password, $user['password'])) {
            self::incrementAttempts($email);
            return false;
        }

        self::clearAttempts($email);
        self::loginUser($user);

        return true;
    }

    public static function login(array $user): void
    {
        self::loginUser($user);
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$cachedUser = null;
    }

    public static function check(): bool
    {
        return Session::has(self::SESSION_KEY);
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    public static function id(): ?int
    {
        $id = Session::get(self::SESSION_KEY);

        return $id !== null ? (int) $id : null;
    }

    public static function user(Connection $db): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $id = self::id();

        if ($id === null) {
            return null;
        }

        self::$cachedUser = $db->queryOne(
            'SELECT * FROM "' . self::TABLE . '" WHERE "id" = ?',
            [$id],
        );

        return self::$cachedUser;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    private static function isLockedOut(string $email): bool
    {
        $key = self::throttleKey($email);
        $data = Session::get($key);

        if (!$data) {
            return false;
        }

        if ($data['attempts'] < self::MAX_LOGIN_ATTEMPTS) {
            return false;
        }

        if (time() - $data['last_attempt'] > self::LOCKOUT_SECONDS) {
            self::clearAttempts($email);
            return false;
        }

        return true;
    }

    private static function remainingAttempts(string $email): int
    {
        $key = self::throttleKey($email);
        $data = Session::get($key);

        if (!$data) {
            return self::MAX_LOGIN_ATTEMPTS;
        }

        return max(0, self::MAX_LOGIN_ATTEMPTS - $data['attempts']);
    }

    private static function loginUser(array $user): void
    {
        Session::regenerate();
        Session::set(self::SESSION_KEY, $user['id']);
        self::$cachedUser = $user;
    }

    private static function incrementAttempts(string $email): void
    {
        $key = self::throttleKey($email);
        $data = Session::get($key, ['attempts' => 0, 'last_attempt' => 0]);
        $data['attempts']++;
        $data['last_attempt'] = time();
        Session::set($key, $data);
    }

    private static function clearAttempts(string $email): void
    {
        Session::remove(self::throttleKey($email));
    }

    private static function throttleKey(string $email): string
    {
        return '_login_attempts_' . md5($email);
    }
}
