<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    private static array $options = [
        'secure' => false,
    ];

    public static function configure(array $options = []): void
    {
        self::$options = array_merge(self::$options, $options);
    }

    public static function start(array $options = []): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        self::$options = array_merge(self::$options, $options);

        session_name('prompt_library_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => (bool) (self::$options['secure'] ?? false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::ensureStarted();

        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        self::ensureStarted();

        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        self::ensureStarted();

        unset($_SESSION[$key]);
    }

    public static function regenerate(): void
    {
        self::ensureStarted();

        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }

        session_destroy();
    }

    public static function setFlash(string $key, mixed $value): void
    {
        self::ensureStarted();

        $_SESSION['_flash'][$key] = $value;
    }

    public static function flash(string $key, mixed $default = null): mixed
    {
        self::ensureStarted();

        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }

    public static function hasFlash(string $key): bool
    {
        self::ensureStarted();

        return isset($_SESSION['_flash'][$key]);
    }

    private static function ensureStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::start();
        }
    }
}
