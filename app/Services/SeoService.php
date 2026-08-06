<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Prompt;

final class SeoService
{
    public static function promptUrl(array $prompt): string
    {
        return app_url('/prompts/' . Prompt::publicIdentifier($prompt));
    }

    public static function description(?string $text, int $limit = 155): string
    {
        $text = trim(strip_tags((string) $text));
        $text = preg_replace('/\s+/', ' ', $text) ?: '';

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit - 1)) . '...';
    }

    public static function canShowAds(bool $noindex = false, int $resultCount = 1): bool
    {
        return filter_var(env('ADSENSE_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN)
            && (string) env('ADSENSE_PUBLISHER_ID', '') !== ''
            && ! $noindex
            && $resultCount > 0;
    }
}

