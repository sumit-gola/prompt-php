<?php

declare(strict_types=1);

namespace App\Services;

final class GoogleTagManagerService
{
    private const CONTAINER_PATTERN = '/^GTM-[A-Z0-9]+$/';

    public static function normalizeContainerId(?string $value): ?string
    {
        $containerId = strtoupper(trim((string) $value));

        return preg_match(self::CONTAINER_PATTERN, $containerId) === 1
            ? $containerId
            : null;
    }

    public static function containerId(): ?string
    {
        return self::normalizeContainerId((string) env('GTM_CONTAINER_ID', ''));
    }
}
