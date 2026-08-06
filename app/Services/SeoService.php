<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Prompt;

final class SeoService
{
    public static function siteName(): string
    {
        return (string) env('APP_NAME', 'MyPromptArt');
    }

    public static function promptUrl(array $prompt): string
    {
        return app_url('/prompts/' . Prompt::publicIdentifier($prompt));
    }

    public static function assetUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return app_url(asset($path));
    }

    public static function defaultShareImageUrl(): string
    {
        return app_url('/assets/img/share-card-library.png');
    }

    public static function defaultShareImageAlt(): string
    {
        return 'MyPromptArt AI prompt library preview';
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

    public static function websiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => self::siteName(),
            'url' => app_url('/'),
            'description' => 'A searchable public library of completed AI image prompts.',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => app_url('/prompts') . '?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public static function organizationSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => self::siteName(),
            'url' => app_url('/'),
        ];
    }

    public static function collectionSchema(string $name, string $description, int $count): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $name,
            'description' => $description,
            'url' => app_url('/prompts'),
            'mainEntity' => [
                '@type' => 'ItemList',
                'numberOfItems' => $count,
            ],
        ];
    }

    public static function webPageSchema(string $name, string $description, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => self::siteName(),
                'url' => app_url('/'),
            ],
        ];
    }

    public static function promptSchema(array $prompt): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => (string) $prompt['title'],
            'description' => self::description((string) $prompt['prompt']),
            'text' => (string) $prompt['prompt'],
            'url' => self::promptUrl($prompt),
            'genre' => (string) $prompt['category'],
            'datePublished' => self::isoDate($prompt['generated_at'] ?? $prompt['created_at'] ?? null),
            'dateModified' => self::isoDate($prompt['updated_at'] ?? null),
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => self::siteName(),
                'url' => app_url('/'),
            ],
        ];

        $image = self::assetUrl($prompt['thumbnail_path'] ?? null);

        if ($image !== null) {
            $schema['image'] = $image;
        }

        return $schema;
    }

    public static function breadcrumbSchema(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                static fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ],
                $items,
                array_keys($items)
            ),
        ];
    }

    private static function isoDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp ? date(DATE_ATOM, $timestamp) : null;
    }
}
