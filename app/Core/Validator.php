<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    public static function required(array &$errors, string $key, mixed $value, string $label): void
    {
        if ($value === null || trim((string) $value) === '') {
            $errors[$key] = $label . ' is required.';
        }
    }

    public static function max(array &$errors, string $key, mixed $value, int $max, string $label): void
    {
        if (mb_strlen(trim((string) $value)) > $max) {
            $errors[$key] = $label . ' must be ' . $max . ' characters or fewer.';
        }
    }

    public static function in(array &$errors, string $key, mixed $value, array $allowed, string $label): void
    {
        if (! in_array((string) $value, $allowed, true)) {
            $errors[$key] = $label . ' is not valid.';
        }
    }

    public static function email(array &$errors, string $key, mixed $value): void
    {
        if (! filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
            $errors[$key] = 'Enter a valid email address.';
        }
    }
}

