<?php

declare(strict_types=1);

namespace App\Services;

final class AdSenseService
{
    private const PUBLISHER_PATTERN = '/^(?:ca-)?pub-(\d{16})$/';

    private const SLOT_PATTERN = '/^\d{10}$/';

    private const SLOT_ENV_KEYS = [
        'home' => 'ADSENSE_HOME_SLOT',
        'library' => 'ADSENSE_LIBRARY_SLOT',
        'detail' => 'ADSENSE_DETAIL_SLOT',
    ];

    public static function normalizePublisherId(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));

        if (preg_match(self::PUBLISHER_PATTERN, $value, $match) !== 1) {
            return null;
        }

        return 'pub-' . $match[1];
    }

    public static function normalizeClientId(?string $value): ?string
    {
        $publisherId = self::normalizePublisherId($value);

        return $publisherId !== null ? 'ca-' . $publisherId : null;
    }

    public static function publisherId(): ?string
    {
        return self::normalizePublisherId((string) env('ADSENSE_PUBLISHER_ID', ''));
    }

    public static function clientId(): ?string
    {
        return self::normalizeClientId((string) env('ADSENSE_PUBLISHER_ID', ''));
    }

    public static function isEnabled(): bool
    {
        return filter_var(env('ADSENSE_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN)
            && self::publisherId() !== null;
    }

    public static function slotId(?string $placement): ?string
    {
        $envKey = self::SLOT_ENV_KEYS[$placement ?? ''] ?? null;

        if ($envKey === null) {
            return null;
        }

        $slot = trim((string) env($envKey, ''));

        if (preg_match(self::SLOT_PATTERN, $slot) !== 1 || preg_match('/^0+$/', $slot) === 1) {
            return null;
        }

        return $slot;
    }

    public static function configuration(bool $pageEligible = false, ?string $placement = null): array
    {
        $publisherId = self::publisherId();
        $clientId = self::clientId();
        $enabled = self::isEnabled();
        $loaderEnabled = $enabled && $pageEligible;

        return [
            'enabled' => $enabled,
            'loader_enabled' => $loaderEnabled,
            'publisher_id' => $publisherId,
            'client_id' => $clientId,
            'placement' => isset(self::SLOT_ENV_KEYS[$placement ?? '']) ? $placement : null,
            'slot_id' => $loaderEnabled ? self::slotId($placement) : null,
        ];
    }

    public static function adsTxtLine(): ?string
    {
        $publisherId = self::publisherId();

        return $publisherId !== null
            ? 'google.com, ' . $publisherId . ', DIRECT, f08c47fec0942fa0'
            : null;
    }
}
