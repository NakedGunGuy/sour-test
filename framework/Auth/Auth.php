<?php

declare(strict_types=1);

namespace Sauerkraut\Auth;

use Sauerkraut\Database\Connection;
use Sauerkraut\Http\Session;

class Auth
{
    private const string SESSION_KEY = '_auth_user_id';
    private const string TABLE = 'users';

    private static ?array $cachedUser = null;

    public static function attempt(Connection $db, string $email, string $password): bool
    {
        $user = $db->queryOne(
            'SELECT * FROM "' . self::TABLE . '" WHERE "email" = ?',
            [$email],
        );

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        self::loginUser($user);

        return true;
    }

    public static function login(array $user): void
    {
        self::loginUser($user);
    }

    public static function logout(): void
    {
        Session::remove(self::SESSION_KEY);
        Session::regenerate();
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

    private static function loginUser(array $user): void
    {
        Session::regenerate();
        Session::set(self::SESSION_KEY, $user['id']);
        self::$cachedUser = $user;
    }
}
