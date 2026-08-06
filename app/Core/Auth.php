<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    private static ?array $user = null;

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);

        if (! $user || ! password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        Session::regenerate();
        Session::put('user_id', (int) $user['id']);
        self::$user = $user;

        return true;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::put('user_id', (int) $user['id']);
        self::$user = $user;
    }

    public static function logout(): void
    {
        self::$user = null;
        Session::destroy();
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        $id = Session::get('user_id');

        if (! is_numeric($id)) {
            return null;
        }

        self::$user = User::find((int) $id);

        return self::$user;
    }
}

