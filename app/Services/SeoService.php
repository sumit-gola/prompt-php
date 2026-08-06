<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Models\Prompt;

final class SeoService
{
    private const CATEGORY_CONTENT = [
        'portrait' => [
            'name' => 'Portrait',
            'description' => 'Explore completed AI portrait prompts for expressive faces, studio lighting, editorial compositions, and polished character photography.',
        ],
        'product' => [
            'name' => 'Product',
            'description' => 'Browse completed AI product prompts for commercial still life, packaging, advertising scenes, and clean studio presentation.',
        ],
        'fashion' => [
            'name' => 'Fashion',
            'description' => 'Discover completed AI fashion prompts for lookbooks, editorial styling, campaign imagery, garments, and accessories.',
        ],
        'lifestyle' => [
            'name' => 'Lifestyle',
            'description' => 'Find completed AI lifestyle prompts for natural moments, travel, interiors, everyday scenes, and creator-led photography.',
        ],
        'art' => [
            'name' => 'Art',
            'description' => 'Explore completed AI art prompts covering illustration, painting, mixed media, conceptual imagery, and experimental visual styles.',
        ],
        'other' => [
            'name' => 'Other',
            'description' => 'Browse completed AI image prompts that cross categories, combine visual approaches, or explore distinctive creative concepts.',
        ],
    ];

    public static function siteName(): string
    {
        return trim((string) env('APP_NAME', 'MyPromptArt')) ?: 'MyPromptArt';
    }

    public static function promptUrl(array $prompt): string
    {
        return app_url('/prompts/' . Prompt::publicIdentifier($prompt));
    }

    public static function categoryUrl(string $category): string
    {
        return app_url('/prompts/category/' . rawurlencode($category));
    }

    public static function listingUrl(string $path, int $page = 1): string
    {
        $url = app_url($path);

        return $page > 1 ? $url . '?page=' . $page : $url;
    }

    public static function assetUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            if (strtolower((string) parse_url(app_url('/'), PHP_URL_SCHEME)) === 'https'
                && strtolower((string) parse_url($path, PHP_URL_SCHEME)) !== 'https') {
                return null;
            }

            return $path;
        }

        return app_url(asset($path));
    }

    public static function defaultShareImageUrl(): string
    {
        return app_url('/assets/img/share-card-public.png');
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

        return rtrim(mb_substr($text, 0, max(1, $limit - 3)), " \t\n\r\0\x0B.,;:-") . '...';
    }

    public static function promptTitle(array $prompt): string
    {
        $suffix = ' AI Image Prompt | ' . self::siteName();
        $available = max(24, 65 - mb_strlen($suffix));

        return self::description((string) $prompt['title'], $available) . $suffix;
    }

    public static function categoryName(string $category): string
    {
        return self::CATEGORY_CONTENT[$category]['name'] ?? ucfirst($category);
    }

    public static function categoryDescription(string $category): string
    {
        return self::CATEGORY_CONTENT[$category]['description']
            ?? 'Browse completed AI image prompts curated for practical creative work.';
    }

    public static function imageMetadata(?string $path, string $alt): ?array
    {
        $url = self::assetUrl($path);

        if ($url === null) {
            return null;
        }

        $metadata = [
            'url' => $url,
            'alt' => self::description($alt, 180),
            'type' => self::imageMimeFromPath($url),
            'width' => null,
            'height' => null,
        ];

        if (preg_match('#^https?://#i', trim((string) $path)) !== 1) {
            $relativePath = ltrim(asset((string) $path), '/');
            $file = public_path($relativePath);
            $info = is_file($file) ? @getimagesize($file) : false;

            if (is_array($info)) {
                $metadata['width'] = (int) ($info[0] ?? 0) ?: null;
                $metadata['height'] = (int) ($info[1] ?? 0) ?: null;
                $metadata['type'] = (string) ($info['mime'] ?? $metadata['type']);
            }
        }

        return $metadata;
    }

    public static function canonicalRedirectUrl(Request $request): ?string
    {
        if (! in_array($request->realMethod(), ['GET', 'HEAD'], true)) {
            return null;
        }

        $base = rtrim((string) env('APP_URL', ''), '/');
        $parts = parse_url($base);

        if ($base === '' || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $canonicalAuthority = strtolower((string) $parts['host']);

        if (isset($parts['port'])) {
            $canonicalAuthority .= ':' . (int) $parts['port'];
        }

        $requestAuthority = strtolower(trim((string) $request->server('HTTP_HOST', '')));
        $forwardedProto = trim(explode(',', (string) $request->server('HTTP_X_FORWARDED_PROTO', ''))[0]);
        $requestScheme = $forwardedProto !== ''
            ? strtolower($forwardedProto)
            : (filter_var($request->server('HTTPS', false), FILTER_VALIDATE_BOOLEAN) ? 'https' : 'http');
        $requestUri = (string) $request->server('REQUEST_URI', $request->path());
        $originalPath = (string) (parse_url($requestUri, PHP_URL_PATH) ?: '/');
        $hasTrailingSlash = $originalPath !== '/' && str_ends_with($originalPath, '/');

        if ($requestAuthority === $canonicalAuthority
            && $requestScheme === strtolower((string) $parts['scheme'])
            && ! $hasTrailingSlash) {
            return null;
        }

        $target = app_url($request->path());
        $query = (string) parse_url($requestUri, PHP_URL_QUERY);

        return $query !== '' ? $target . '?' . $query : $target;
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
            '@id' => app_url('/#website'),
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
            '@id' => app_url('/#organization'),
            'name' => self::siteName(),
            'url' => app_url('/'),
        ];
    }

    public static function collectionSchema(
        string $name,
        string $description,
        string $url,
        int $count,
        array $prompts = []
    ): array {
        $items = [];

        foreach (array_values($prompts) as $index => $prompt) {
            $item = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => self::promptUrl($prompt),
                'name' => (string) $prompt['title'],
            ];
            $image = self::assetUrl($prompt['thumbnail_path'] ?? null);

            if ($image !== null) {
                $item['image'] = $image;
            }

            $items[] = $item;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            '@id' => $url . '#collection',
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'isPartOf' => ['@id' => app_url('/#website')],
            'mainEntity' => [
                '@type' => 'ItemList',
                'numberOfItems' => $count,
                'itemListElement' => $items,
            ],
        ];
    }

    public static function webPageSchema(string $name, string $description, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            '@id' => $url . '#webpage',
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'isPartOf' => ['@id' => app_url('/#website')],
        ];
    }

    public static function promptSchema(array $prompt): array
    {
        $url = self::promptUrl($prompt);
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            '@id' => $url . '#creativework',
            'name' => (string) $prompt['title'],
            'description' => self::description((string) $prompt['prompt']),
            'text' => (string) $prompt['prompt'],
            'url' => $url,
            'mainEntityOfPage' => $url,
            'genre' => self::categoryName((string) $prompt['category']),
            'isPartOf' => ['@id' => app_url('/#website')],
        ];

        $published = self::isoDate($prompt['generated_at'] ?? $prompt['created_at'] ?? null);
        $modified = self::isoDate($prompt['updated_at'] ?? null);

        if ($published !== null) {
            $schema['datePublished'] = $published;
        }

        if ($modified !== null) {
            $schema['dateModified'] = $modified;
        }

        $image = self::imageMetadata(
            $prompt['thumbnail_path'] ?? null,
            (string) $prompt['title'] . ' AI image prompt preview'
        );

        if ($image !== null) {
            $imageObject = [
                '@type' => 'ImageObject',
                'url' => $image['url'],
                'contentUrl' => $image['url'],
                'caption' => $image['alt'],
            ];

            if ($image['width'] !== null && $image['height'] !== null) {
                $imageObject['width'] = $image['width'];
                $imageObject['height'] = $image['height'];
            }

            $schema['image'] = $imageObject;
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

    public static function isoDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp ? date(DATE_ATOM, $timestamp) : null;
    }

    public static function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function imageMimeFromPath(string $path): string
    {
        $imagePath = (string) parse_url($path, PHP_URL_PATH);

        return match (true) {
            preg_match('/\.jpe?g$/i', $imagePath) === 1 => 'image/jpeg',
            preg_match('/\.webp$/i', $imagePath) === 1 => 'image/webp',
            preg_match('/\.avif$/i', $imagePath) === 1 => 'image/avif',
            preg_match('/\.gif$/i', $imagePath) === 1 => 'image/gif',
            default => 'image/png',
        };
    }
}
