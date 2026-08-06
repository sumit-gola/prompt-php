<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Session;

function base_path(string $path = ''): string
{
    return BASE_PATH . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function public_path(string $path = ''): string
{
    $publicBase = rtrim((string) env('PUBLIC_PATH', base_path('public')), '/');

    return $publicBase . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return match (strtolower((string) $value)) {
        'true' => true,
        'false' => false,
        'null' => null,
        default => $value,
    };
}

function app_url(string $path = ''): string
{
    $base = rtrim((string) env('APP_URL', 'http://127.0.0.1:8080'), '/');
    return $base . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function url(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    $path = ltrim($path, '/');

    if (str_starts_with($path, 'prompts/')) {
        $path = 'storage/' . $path;
    }

    return '/' . $path;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function flash(string $key, mixed $default = null): mixed
{
    return Session::flash($key, $default);
}

function old(string $key, mixed $default = ''): mixed
{
    $old = Session::get('old', []);

    return is_array($old) && array_key_exists($key, $old) ? $old[$key] : $default;
}

function selected(mixed $current, mixed $expected): string
{
    return (string) $current === (string) $expected ? ' selected' : '';
}

function checked(mixed $current, mixed $expected = true): string
{
    return $current == $expected ? ' checked' : '';
}

function str_limit(string $value, int $limit = 140): string
{
    if (mb_strlen($value) <= $limit) {
        return $value;
    }

    return rtrim(mb_substr($value, 0, $limit - 1)) . '...';
}

function str_limit_words(string $value, int $limit = 140): string
{
    $normalized = trim((string) preg_replace('/\s+/', ' ', $value));

    if (mb_strlen($normalized) <= $limit) {
        return $normalized;
    }

    $slice = rtrim(mb_substr($normalized, 0, max(0, $limit - 3)));
    $lastSpace = mb_strrpos($slice, ' ');

    if ($lastSpace !== false && $lastSpace >= (int) floor($limit * .55)) {
        $slice = mb_substr($slice, 0, $lastSpace);
    }

    return rtrim($slice, " \t\n\r\0\x0B.,;:-") . '...';
}
