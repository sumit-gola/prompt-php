<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Models\Prompt;

final class SeoService
{
    private const SITE_NAME = 'MyPromptArt';

    private const SITE_ALTERNATE_NAME = 'MPA';

    private const CATEGORY_CONTENT = [
        'portrait' => [
            'name' => 'Portrait',
            'title' => 'Portrait AI Prompts for Photo Editing',
            'heading' => 'Portrait AI Prompts',
            'meta' => 'Explore %d curated portrait AI prompts for cinematic photos, studio headshots, fashion editorials and identity-preserving photo edits. Preview and copy each prompt free.',
            'intro' => 'Discover copy-ready portrait AI prompts designed for realistic headshots, cinematic portraits, fashion photography, profile pictures and creative photo transformations. Filter by style, lighting, composition and supported AI model.',
        ],
        'product' => [
            'name' => 'Product',
            'title' => 'Product AI Prompts for Commercial Photography',
            'heading' => 'Product AI Prompts',
            'meta' => 'Explore %d curated product AI prompts for ecommerce photos, packaging, advertising, studio still life and polished brand visuals. Preview and copy prompts free.',
            'intro' => 'Discover copy-ready product AI prompts for ecommerce listings, advertising campaigns, packaging mockups, studio still life and branded compositions. Explore lighting, backgrounds, camera angles and supported AI models.',
        ],
        'fashion' => [
            'name' => 'Fashion',
            'title' => 'Fashion AI Prompts for Editorial Photography',
            'heading' => 'Fashion AI Prompts',
            'meta' => 'Explore %d curated fashion AI prompts for editorial shoots, campaign images, lookbooks, styling concepts and garment photography. Preview and copy prompts free.',
            'intro' => 'Discover copy-ready fashion AI prompts for editorial shoots, lookbooks, campaigns, runway concepts, garment details and creative styling. Explore lighting, poses, composition and supported AI models.',
        ],
        'lifestyle' => [
            'name' => 'Lifestyle',
            'title' => 'Lifestyle AI Prompts for Natural Photography',
            'heading' => 'Lifestyle AI Prompts',
            'meta' => 'Explore %d curated lifestyle AI prompts for travel, interiors, candid moments, creator content and natural photography. Preview and copy prompts free.',
            'intro' => 'Discover copy-ready lifestyle AI prompts for candid moments, travel scenes, interiors, everyday storytelling and creator-led photography. Explore mood, setting, lighting, composition and supported AI models.',
        ],
        'art' => [
            'name' => 'Art',
            'title' => 'Art AI Prompts for Creative Image Generation',
            'heading' => 'Art AI Prompts',
            'meta' => 'Explore %d curated art AI prompts for illustration, painting, concept art, mixed media and experimental visual styles. Preview and copy prompts free.',
            'intro' => 'Discover copy-ready art AI prompts for illustration, painting, concept art, mixed media and experimental visual styles. Explore medium, color, composition, artistic direction and supported AI models.',
        ],
        'other' => [
            'name' => 'Other',
            'title' => 'Creative AI Prompts for Visual Experiments',
            'heading' => 'Creative AI Prompts',
            'meta' => 'Explore %d curated creative AI prompts that combine categories, visual approaches and experimental concepts. Preview and copy prompts free.',
            'intro' => 'Discover copy-ready creative AI prompts that cross categories, combine visual approaches and explore distinctive concepts. Refine the style, composition, mood and supported AI model for your project.',
        ],
    ];

    public static function siteName(): string
    {
        return self::SITE_NAME;
    }

    public static function siteAlternateName(): string
    {
        return self::SITE_ALTERNATE_NAME;
    }

    public static function promptUrl(array $prompt): string
    {
        return app_url('/prompts/' . Prompt::publicIdentifier($prompt));
    }

    public static function categoryUrl(string $category): string
    {
        return app_url('/ai-prompts/' . rawurlencode(strtolower($category)));
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
        return app_url('/assets/img/share-card-public.png?v=20260807brand1');
    }

    public static function defaultShareImageAlt(): string
    {
        return 'MyPromptArt curated AI prompt collection preview';
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
        return self::categoryIntro($category);
    }

    public static function categoryMetaTitle(string $category): string
    {
        return self::CATEGORY_CONTENT[$category]['title']
            ?? self::categoryName($category) . ' AI Prompts';
    }

    public static function categoryHeading(string $category): string
    {
        return self::CATEGORY_CONTENT[$category]['heading']
            ?? self::categoryName($category) . ' AI Prompts';
    }

    public static function categoryMetaDescription(string $category, int $count): string
    {
        $template = self::CATEGORY_CONTENT[$category]['meta']
            ?? 'Explore %d curated AI image prompts ready to preview and copy free.';

        return sprintf($template, max(0, $count));
    }

    public static function categoryIntro(string $category): string
    {
        return self::CATEGORY_CONTENT[$category]['intro']
            ?? 'Discover copy-ready AI image prompts curated for practical creative work. Explore styles, composition and supported AI models.';
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
        return AdSenseService::isEnabled()
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
            'alternateName' => self::siteAlternateName(),
            'url' => app_url('/'),
            'description' => 'Discover curated AI image and photo-editing prompts for portraits, fashion, products, art and lifestyle.',
            'publisher' => ['@id' => app_url('/#organization')],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => app_url('/prompts') . '?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public static function organizationSchema(): array
    {
        $logoUrl = app_url('/assets/img/my-prompt-art-logo.webp');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => app_url('/#organization'),
            'name' => self::siteName(),
            'alternateName' => self::siteAlternateName(),
            'url' => app_url('/'),
            'logo' => [
                '@type' => 'ImageObject',
                '@id' => app_url('/#logo'),
                'url' => $logoUrl,
                'contentUrl' => $logoUrl,
                'width' => 1200,
                'height' => 408,
                'caption' => self::siteName(),
            ],
            'image' => ['@id' => app_url('/#logo')],
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
            $image = PromptImageService::metadata($prompt);

            if ($image !== null) {
                $item['image'] = $image['url'];
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
            'publisher' => ['@id' => app_url('/#organization')],
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
            'publisher' => ['@id' => app_url('/#organization')],
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
            'keywords' => [self::categoryName((string) $prompt['category']) . ' AI prompts'],
            'inLanguage' => 'en',
            'isPartOf' => ['@id' => app_url('/#website')],
            'publisher' => ['@id' => app_url('/#organization')],
        ];

        $testedModels = self::testedModelNames($prompt['tested_models'] ?? null);

        if ($testedModels !== []) {
            $schema['keywords'] = array_merge($schema['keywords'], $testedModels);
            $schema['mentions'] = array_map(
                static fn (string $model): array => [
                    '@type' => 'SoftwareApplication',
                    'name' => $model,
                    'description' => 'AI model recorded as tested with this prompt.',
                ],
                $testedModels
            );
        }

        $published = self::isoDate($prompt['generated_at'] ?? $prompt['created_at'] ?? null);
        $modified = self::isoDate($prompt['updated_at'] ?? null);

        if ($published !== null) {
            $schema['datePublished'] = $published;
        }

        if ($modified !== null) {
            $schema['dateModified'] = $modified;
        }

        $image = PromptImageService::metadata($prompt);

        if ($image !== null) {
            $imageObject = [
                '@type' => 'ImageObject',
                'url' => $image['url'],
                'contentUrl' => $image['url'],
                'caption' => $image['caption'],
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

    private static function testedModelNames(mixed $value): array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return [];
        }

        $models = preg_split('/\s*(?:,|\/|;|\|)\s*/u', $value) ?: [];
        $unique = [];

        foreach ($models as $model) {
            $model = trim($model);

            if ($model === '') {
                continue;
            }

            $key = mb_strtolower($model);
            $unique[$key] ??= $model;
        }

        return array_values($unique);
    }
}
