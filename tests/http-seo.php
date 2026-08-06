<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\Prompt;
use App\Services\AdSenseService;

require dirname(__DIR__) . '/bootstrap/app.php';

$baseUrl = rtrim((string) (getenv('SEO_TEST_BASE_URL') ?: env('APP_URL', 'http://127.0.0.1:8080')), '/');
$failures = [];

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$request = static function (string $path, string $userAgent = 'MyPromptArt SEO smoke test') use ($baseUrl): array {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: {$userAgent}\r\nAccept: text/html,application/xml,text/plain\r\n",
            'ignore_errors' => true,
            'follow_location' => 0,
            'timeout' => 15,
        ],
    ]);
    $body = file_get_contents($baseUrl . $path, false, $context);
    $headers = $http_response_header ?? [];
    preg_match('#HTTP/\S+\s+(\d{3})#', (string) ($headers[0] ?? ''), $statusMatch);
    $headerMap = [];

    foreach (array_slice($headers, 1) as $header) {
        if (! str_contains($header, ':')) {
            continue;
        }

        [$name, $value] = explode(':', $header, 2);
        $headerMap[strtolower(trim($name))] = trim($value);
    }

    return [(int) ($statusMatch[1] ?? 0), (string) $body, $headerMap];
};

$meta = static function (string $html, string $name, bool $property = false): ?string {
    $attribute = $property ? 'property' : 'name';
    $pattern = '#<meta[^>]*' . $attribute . '=["\']' . preg_quote($name, '#') . '["\'][^>]*content=["\']([^"\']*)["\'][^>]*>#i';

    if (preg_match($pattern, $html, $match) === 1) {
        return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    $reverse = '#<meta[^>]*content=["\']([^"\']*)["\'][^>]*' . $attribute . '=["\']' . preg_quote($name, '#') . '["\'][^>]*>#i';

    return preg_match($reverse, $html, $match) === 1
        ? html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')
        : null;
};

$canonical = static function (string $html): ?string {
    return preg_match('#<link[^>]*rel=["\']canonical["\'][^>]*href=["\']([^"\']+)["\']#i', $html, $match) === 1
        ? html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')
        : null;
};

$assertJsonLd = static function (string $html, string $label) use ($assert): void {
    preg_match_all('#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $matches);
    $assert(($matches[1] ?? []) !== [], "{$label} should include JSON-LD.");

    foreach ($matches[1] ?? [] as $json) {
        json_decode($json, true);
        $assert(json_last_error() === JSON_ERROR_NONE, "{$label} contains invalid JSON-LD.");
    }
};

[$status, $home] = $request('/');
$assert($status === 200, 'Homepage should return HTTP 200.');
$assert($canonical($home) === $baseUrl . '/', 'Homepage should have an absolute self-canonical.');
$assert(substr_count(strtolower($home), '<h1') === 1, 'Homepage should render exactly one H1.');
$assert(str_contains($home, '<title>AI Image &amp; Photo Editing Prompts | MyPromptArt</title>'), 'Homepage title should target AI image and photo-editing prompts.');
$assert(
    $meta($home, 'description') === 'Discover 1,000+ curated AI image and photo-editing prompts for portraits, fashion, products, art and lifestyle. Preview, copy and create better AI images.',
    'Homepage should render its unique keyword-focused meta description.'
);
$assert(str_contains($home, '<h1>1,000+ AI Image Prompts for Photo Editing</h1>'), 'Homepage H1 should identify the image and photo-editing focus.');
$assert(
    str_contains($home, 'Browse curated, copy-ready AI prompts for portraits, product photography, fashion, lifestyle and digital art.'),
    'Homepage should include supporting keyword-focused copy.'
);
$assert($meta($home, 'og:site_name', true) === 'MyPromptArt', 'Homepage Open Graph site name should use MyPromptArt.');
$assert(str_contains($home, '"@type":"WebSite"'), 'Homepage should include WebSite structured data.');
$assert(str_contains($home, '"@type":"Organization"'), 'Homepage should include Organization structured data.');
$assert(
    str_contains($home, '"url":"' . $baseUrl . '/assets/img/my-prompt-art-logo.webp"'),
    'Homepage Organization data should expose the canonical site logo.'
);
$assert(str_contains($home, '"name":"MyPromptArt"'), 'Homepage structured data should use the canonical brand name.');
$assert(str_contains($home, '"alternateName":"MPA"'), 'Homepage structured data should expose MPA as the alternate name.');
$assert(str_contains($home, '<strong>MyPromptArt</strong>'), 'Homepage footer should use the canonical brand name.');
$assertJsonLd($home, 'Homepage');
$homeAdsense = AdSenseService::configuration(str_contains($home, 'class="prompt-card"'), 'home');

if ($homeAdsense['client_id'] !== null) {
    $assert(
        $meta($home, 'google-adsense-account') === $homeAdsense['client_id'],
        'Homepage should expose the normalized AdSense account verification meta tag.'
    );
} else {
    $assert($meta($home, 'google-adsense-account') === null, 'Invalid or absent AdSense configuration must not render an account meta tag.');
}

if ($homeAdsense['loader_enabled']) {
    $loaderUrl = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . $homeAdsense['client_id'];
    $assert(substr_count($home, $loaderUrl) === 1, 'Eligible homepage should render exactly one normalized AdSense loader.');
    $assert(! str_contains($home, 'client=' . $homeAdsense['publisher_id']), 'AdSense loader must not use the pub- seller identifier.');

    if ($homeAdsense['slot_id'] !== null) {
        $assert(str_contains($home, 'data-ad-slot="' . $homeAdsense['slot_id'] . '"'), 'Homepage should render its configured manual ad slot.');
    } else {
        $assert(! str_contains($home, '<ins class="adsbygoogle"'), 'Homepage should not render a manual ad unit without a real slot.');
    }
} else {
    $assert(! str_contains($home, 'pagead2.googlesyndication.com'), 'Disabled or ineligible homepage must not load AdSense.');
    $assert(! str_contains($home, '<ins class="adsbygoogle"'), 'Disabled or ineligible homepage must not render a manual ad unit.');
}

$assert(! str_contains($home, '0000000000'), 'Homepage must never expose the fake AdSense slot.');

foreach ([
    '/about' => 'About MyPromptArt',
    '/contact' => 'Contact MyPromptArt',
    '/privacy-policy' => 'MyPromptArt',
    '/terms' => 'MyPromptArt',
] as $brandPath => $expectedBrand) {
    [$brandStatus, $brandPage] = $request($brandPath);
    $assert($brandStatus === 200, "{$brandPath} should return HTTP 200.");
    $assert(str_contains($brandPage, $expectedBrand), "{$brandPath} should use the MyPromptArt brand.");
    $assert($meta($brandPage, 'og:site_name', true) === 'MyPromptArt', "{$brandPath} should use MyPromptArt in Open Graph metadata.");
    if ($homeAdsense['loader_enabled']) {
        $assert(substr_count($brandPage, $loaderUrl) === 1, "{$brandPath} should render exactly one AdSense loader.");
    } else {
        $assert(! str_contains($brandPage, 'pagead2.googlesyndication.com'), "{$brandPath} should not load AdSense when it is disabled.");
    }
    $assert(! str_contains($brandPage, '<ins class="adsbygoogle"'), "{$brandPath} should remain free from manual ad units during onboarding.");
}

[$status, $about] = $request('/about');
$assert($status === 200, 'About page should return HTTP 200.');
$assert(str_contains($about, 'How prompts are selected'), 'About page should explain prompt selection.');
$assert(str_contains($about, 'What “tested with” means'), 'About page should disclose the model-testing standard.');
$assert(str_contains($about, 'How source material is handled'), 'About page should explain source and rights handling.');
$assert(str_contains($about, 'Gemini, ChatGPT Image, Midjourney'), 'About page should name supported image-model families.');

[$status, $contact] = $request('/contact');
$assert($status === 200, 'Contact page should return HTTP 200.');
$assert(str_contains($contact, 'mailto:hello@mypromptart.com'), 'Contact page should publish the support email address.');
$assert(str_contains($contact, '1&ndash;2 business days'), 'Contact page should publish the expected response time.');

[$status, $library] = $request('/prompts');
$assert($status === 200, 'Library should return HTTP 200.');
$assert($canonical($library) === $baseUrl . '/prompts', 'Library should have a clean canonical.');
$assert($meta($library, 'robots') === 'index,follow', 'Clean library should be indexable.');
$assert(! str_contains($library, 'name="keywords"'), 'Library should not render meta keywords.');

if (str_contains($library, '?page=2')) {
    [$status, $pageTwo] = $request('/prompts?page=2');
    $assert($status === 200, 'Valid page two should return HTTP 200.');
    $assert($canonical($pageTwo) === $baseUrl . '/prompts?page=2', 'Page two should be self-canonical.');
    $assert(preg_match('#<title>[^<]*Page 2[^<]*MyPromptArt</title>#', $pageTwo) === 1, 'Page two title should identify its pagination position.');
}

[$status, $emptySearch] = $request('/prompts?q=seo-smoke-no-result-3f92d0a1');
$assert($status === 200, 'Empty search should return HTTP 200.');
$assert($meta($emptySearch, 'robots') === 'noindex,follow', 'Search results should be noindex,follow.');
$assert(! str_contains($emptySearch, 'pagead2.googlesyndication.com'), 'Empty noindex results should not render AdSense.');

[$status, $redundantSort] = $request('/prompts?sort=newest');
$assert($status === 200, 'Explicit default sorting should keep working.');
$assert($meta($redundantSort, 'robots') === 'noindex,follow', 'Explicit sorting URLs should be noindex,follow.');
$assert($canonical($redundantSort) === $baseUrl . '/prompts', 'Explicit default sorting should canonicalize to the clean library.');

preg_match('#href=["\'](/ai-prompts/[a-z]+)["\']#', $home, $categoryMatch);
$categoryPath = $categoryMatch[1] ?? '/ai-prompts/portrait';
$categorySlug = basename($categoryPath);
[$status, $category] = $request($categoryPath);
$assert($status === 200, 'A populated category page should return HTTP 200.');
$assert($canonical($category) === $baseUrl . $categoryPath, 'Category page should be self-canonical.');
$assert($meta($category, 'robots') === 'index,follow', 'A clean populated category page should be indexable.');
$assert(str_contains($category, 'aria-label="Breadcrumb"'), 'Category page should render visible breadcrumbs.');
$assert(str_contains($category, '>MyPromptArt</a>'), 'Category breadcrumbs should identify the homepage as MyPromptArt.');
$assert(str_contains($category, 'AI Prompts</h1>'), 'Category page should render a category-specific H1.');
$assert(str_contains((string) $meta($category, 'description'), 'curated ' . $categorySlug . ' AI prompts'), 'Category meta description should name the category and current collection.');
$assert(str_contains($category, 'copy-ready'), 'Category page should include substantive introductory copy.');
$assert(str_contains($category, '"@type":"CollectionPage"'), 'Category page should include CollectionPage structured data.');
$assert(str_contains($category, '"@type":"BreadcrumbList"'), 'Category page should include BreadcrumbList structured data.');
$assertJsonLd($category, 'Category page');

[$status, $queryCategory] = $request('/prompts?category=' . rawurlencode($categorySlug));
$assert($status === 200, 'Legacy query-string category filtering should keep working.');
$assert($meta($queryCategory, 'robots') === 'noindex,follow', 'Query-string category duplicates should be noindex,follow.');
$assert($canonical($queryCategory) === $baseUrl . $categoryPath, 'Query-string category should canonicalize to its clean category page.');

[$status, , $headers] = $request('/prompts/category/' . rawurlencode($categorySlug) . '?page=2');
$assert($status === 301, 'Legacy path-based category URLs should redirect permanently.');
$assert(($headers['location'] ?? null) === $categoryPath . '?page=2', 'Legacy category redirects should preserve pagination.');

[$status, $sortedCategory] = $request($categoryPath . '?sort=oldest');
$assert($status === 200, 'Category sorting should keep working.');
$assert($meta($sortedCategory, 'robots') === 'noindex,follow', 'Sorted category URLs should be noindex,follow.');
$assert($canonical($sortedCategory) === $baseUrl . $categoryPath, 'Sorted category URLs should canonicalize to the clean landing page.');

[$status, $robots] = $request('/robots.txt');
$assert($status === 200, 'robots.txt should return HTTP 200.');
$assert(str_contains($robots, 'Sitemap: ' . $baseUrl . '/sitemap.xml'), 'robots.txt should advertise the canonical sitemap.');

[$status, $adsTxt, $adsHeaders] = $request('/ads.txt');
$assert($status === 200, 'ads.txt should return HTTP 200.');
$assert(($adsHeaders['content-type'] ?? null) === 'text/plain; charset=UTF-8', 'ads.txt should use the plain-text UTF-8 content type.');
$assert(($adsHeaders['cache-control'] ?? null) === 'public, max-age=3600', 'ads.txt should use a short public cache policy.');
$expectedAdsTxt = AdSenseService::adsTxtLine();
$assert(
    $adsTxt === ($expectedAdsTxt !== null ? $expectedAdsTxt . "\n" : ''),
    'ads.txt should contain exactly the normalized configured seller record or a safe empty response.'
);
$assert(! str_contains($adsTxt, 'ca-pub-'), 'ads.txt must never contain the ca-pub- client identifier.');

[$status, $sitemap] = $request('/sitemap.xml');
$assert($status === 200, 'sitemap.xml should return HTTP 200.');
$assert(str_starts_with(ltrim($sitemap), '<?xml version="1.0" encoding="UTF-8"?>'), 'Sitemap should be UTF-8 XML.');
$assert(str_contains($sitemap, $baseUrl . '/ai-prompts/'), 'Sitemap should include clean category landing pages.');
$assert(! str_contains($sitemap, $baseUrl . '/prompts/category/'), 'Sitemap should exclude legacy category URLs.');
$assert(
    preg_match('#<loc>' . preg_quote($baseUrl, '#') . '/prompts/\d+</loc>#', $sitemap) !== 1,
    'Sitemap prompt URLs should use slugs instead of legacy numeric IDs.'
);

preg_match_all('#<loc>([^<]+)</loc>#', $sitemap, $sitemapMatches);
$sitemapUrls = array_map(
    static fn (string $url): string => html_entity_decode($url, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
    $sitemapMatches[1] ?? []
);
$assert($sitemapUrls !== [], 'Sitemap should contain canonical URLs.');
$assert(count($sitemapUrls) === count(array_unique($sitemapUrls)), 'Sitemap URLs should be unique.');
$preferredOrigin = parse_url($baseUrl);

foreach ($sitemapUrls as $sitemapUrl) {
    $parts = parse_url($sitemapUrl);
    $path = (string) ($parts['path'] ?? '');
    $actualPort = isset($parts['port']) ? (int) $parts['port'] : null;
    $preferredPort = isset($preferredOrigin['port']) ? (int) $preferredOrigin['port'] : null;

    $assert(
        ($parts['scheme'] ?? null) === ($preferredOrigin['scheme'] ?? null)
            && ($parts['host'] ?? null) === ($preferredOrigin['host'] ?? null)
            && $actualPort === $preferredPort,
        "Sitemap URL should use the preferred origin: {$sitemapUrl}"
    );
    $assert(
        ! isset($parts['query']) && ! isset($parts['fragment']),
        "Sitemap URL should not contain a query or fragment: {$sitemapUrl}"
    );
    $assert(
        preg_match('#^/(?:admin|login|register)(?:/|$)#', $path) !== 1,
        "Sitemap should exclude private and authentication routes: {$sitemapUrl}"
    );
    $assert(! str_starts_with($path, '/prompts/category/'), "Sitemap should exclude legacy category URLs: {$sitemapUrl}");
    $assert(preg_match('#^/prompts/\d+$#', $path) !== 1, "Sitemap should exclude numeric prompt URLs: {$sitemapUrl}");
}

foreach ($sitemapUrls as $sitemapUrl) {
    $path = (string) parse_url($sitemapUrl, PHP_URL_PATH);

    if (preg_match('#^/prompts/[^/]+$#', $path) === 1) {
        continue;
    }

    [$sitemapStatus, $sitemapPage, $sitemapHeaders] = $request($path);
    $assert($sitemapStatus === 200, "Non-prompt sitemap URL should return HTTP 200 without redirecting: {$sitemapUrl}");
    $assert($canonical($sitemapPage) === $sitemapUrl, "Non-prompt sitemap URL should be self-canonical: {$sitemapUrl}");
    $assert($meta($sitemapPage, 'robots') === 'index,follow', "Non-prompt sitemap URL should be indexable: {$sitemapUrl}");
    $assert(
        ! str_contains(strtolower((string) ($sitemapHeaders['x-robots-tag'] ?? '')), 'noindex'),
        "Non-prompt sitemap URL should not send an X-Robots-Tag noindex directive: {$sitemapUrl}"
    );
}

[$chunkStatus, $promptChunk] = $request('/sitemaps/prompts-1.xml');
$assert($chunkStatus === 200, 'Prompt sitemap chunks should be routable.');
$assert(str_contains($promptChunk, '<urlset'), 'Prompt sitemap chunk should be a URL set.');

if (function_exists('simplexml_load_string')) {
    $assert(simplexml_load_string($sitemap) !== false, 'Sitemap XML should parse successfully.');
}

preg_match('~href=["\'](/prompts/(?!category/)(?!\d+(?:["\']))[a-z0-9][^"\'?#]*)["\']~i', $library, $linkedPromptMatch);
$linkedPromptPath = (string) ($linkedPromptMatch[1] ?? '');
$promptUrl = $linkedPromptPath !== '' ? $baseUrl . $linkedPromptPath : '';

if ($promptUrl === '') {
    preg_match('#<loc>(' . preg_quote($baseUrl, '#') . '/prompts/(?!category/)[^<]+)</loc>#', $sitemap, $promptMatch);
    $promptUrl = html_entity_decode((string) ($promptMatch[1] ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
} else {
    $assert(
        in_array($promptUrl, $sitemapUrls, true),
        'A prompt linked from the public library should appear in the canonical sitemap.'
    );
}

if ($promptUrl !== '') {
    $promptPath = (string) parse_url($promptUrl, PHP_URL_PATH);
    [$status, $promptPage] = $request($promptPath, 'facebookexternalhit/1.1');
    $assert($status === 200, 'A sitemap prompt should return HTTP 200.');
    $assert($canonical($promptPage) === $promptUrl, 'Prompt detail should be self-canonical.');
    $assert($meta($promptPage, 'og:image', true) !== null, 'Prompt detail should render an Open Graph image.');
    $assert(str_contains($promptPage, 'aria-label="Breadcrumb"'), 'Prompt detail should render visible breadcrumbs.');
    $assert(str_contains($promptPage, '>MyPromptArt</a>'), 'Prompt breadcrumbs should identify the homepage as MyPromptArt.');
    $assert(str_contains($promptPage, 'href="' . $baseUrl . '/ai-prompts/'), 'Prompt breadcrumbs should link directly to a clean category landing page.');
    $assert(! str_contains($promptPage, '>Prompts</a>'), 'Prompt breadcrumbs should not include a redundant generic prompts level.');
    $assert(str_contains($promptPage, 'Curated by'), 'Prompt detail should identify its curator.');
    $assert(str_contains($promptPage, 'Tested with'), 'Prompt detail should disclose recorded model testing.');
    $assert(str_contains($promptPage, 'Last reviewed'), 'Prompt detail should disclose its editorial review status.');
    preg_match('#<div class="prompt-placeholder">(.*?)</div>#is', $promptPage, $promptMediaMatch);

    if (str_contains((string) ($promptMediaMatch[1] ?? ''), '<img')) {
        preg_match('#<img\\b[^>]*\\balt="([^"]+)"#is', (string) $promptMediaMatch[1], $promptAltMatch);
        $promptAlt = trim(html_entity_decode((string) ($promptAltMatch[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $assert(
            $promptAlt !== '' && $promptAlt !== 'AI-generated image preview',
            'A real prompt preview image should have descriptive alt text.'
        );
    }
    $assert(str_contains($promptPage, '"@type":"CreativeWork"'), 'Prompt detail should include CreativeWork structured data.');
    $assert(str_contains($promptPage, '"publisher":{"@id":"' . $baseUrl . '/#organization"}'), 'Prompt CreativeWork should identify its publisher.');
    $assert(! str_contains($promptPage, '"aggregateRating"'), 'Prompt detail must not contain invented rating schema.');
    $assertJsonLd($promptPage, 'Prompt detail');
}

$fullSitemapAudit = filter_var((string) (getenv('SEO_TEST_FULL_SITEMAP') ?: 'false'), FILTER_VALIDATE_BOOLEAN);

if ($fullSitemapAudit) {
    foreach ($sitemapUrls as $sitemapUrl) {
        $path = (string) parse_url($sitemapUrl, PHP_URL_PATH);

        if (preg_match('#^/prompts/[^/]+$#', $path) !== 1) {
            continue;
        }

        [$sitemapStatus, $sitemapPage, $sitemapHeaders] = $request($path);
        $assert($sitemapStatus === 200, "Prompt sitemap URL should return HTTP 200 without redirecting: {$sitemapUrl}");
        $assert($canonical($sitemapPage) === $sitemapUrl, "Prompt sitemap URL should be self-canonical: {$sitemapUrl}");
        $assert($meta($sitemapPage, 'robots') === 'index,follow', "Prompt sitemap URL should be indexable: {$sitemapUrl}");
        $assert(
            ! str_contains(strtolower((string) ($sitemapHeaders['x-robots-tag'] ?? '')), 'noindex'),
            "Prompt sitemap URL should not send an X-Robots-Tag noindex directive: {$sitemapUrl}"
        );
    }
}

[$status, $missing, $headers] = $request('/prompts/seo-smoke-definitely-missing');
$assert($status === 404, 'Missing prompt should return HTTP 404.');
$assert($meta($missing, 'robots') === 'noindex,nofollow', 'Missing prompt should render noindex,nofollow.');
$assert(($headers['x-robots-tag'] ?? null) === 'noindex, nofollow', 'Missing prompt should send X-Robots-Tag.');
$assert(! str_contains($missing, 'pagead2.googlesyndication.com'), 'Missing prompt pages should not load AdSense.');
$assert(! str_contains($missing, '<ins class="adsbygoogle"'), 'Missing prompt pages should not render manual ad units.');

[$status, $login, $headers] = $request('/login');
$assert($status === 200, 'Login page should return HTTP 200.');
$assert($meta($login, 'robots') === 'noindex,nofollow', 'Login page should render noindex,nofollow.');
$assert(($headers['x-robots-tag'] ?? null) === 'noindex, nofollow', 'Login page should send X-Robots-Tag.');
$assert(str_contains($login, '>MPA</span>'), 'Login branding should use the MPA mark.');
$assert(! str_contains($login, '>PL</span>'), 'Login branding should not use the legacy PL mark.');
$assert(! str_contains($login, 'google-adsense-account'), 'Login page should not expose AdSense verification markup.');
$assert(! str_contains($login, 'pagead2.googlesyndication.com'), 'Login page should not load AdSense.');

[$status, $admin] = $request('/admin');
$assert(in_array($status, [302, 403], true), 'Unauthenticated admin access should redirect or return forbidden.');
$assert(! str_contains($admin, 'google-adsense-account'), 'Admin responses should not expose AdSense verification markup.');
$assert(! str_contains($admin, 'pagead2.googlesyndication.com'), 'Admin responses should not load AdSense.');

$missingSlugCount = (int) Database::pdo()->query(
    "SELECT COUNT(*) FROM prompts WHERE source_slug IS NULL OR source_slug = ''"
)->fetchColumn();
$assert($missingSlugCount === 0, 'Every prompt should have a stored public slug.');

$privatePrompt = Database::pdo()->query(
    "SELECT id, source_slug FROM prompts WHERE status <> 'completed' OR prompt IS NULL OR prompt = '' LIMIT 1"
)->fetch();

if ($privatePrompt) {
    $identifier = Prompt::publicIdentifier($privatePrompt);
    [$status] = $request('/prompts/' . rawurlencode($identifier));
    $assert($status === 404, 'A non-public prompt must return HTTP 404.');
    $assert(! str_contains($sitemap, '<loc>' . $baseUrl . '/prompts/' . $identifier . '</loc>'), 'Sitemap must exclude non-public prompts.');
}

$completedWithSlug = Database::pdo()->query(
    "SELECT id, source_slug FROM prompts
     WHERE status = 'completed' AND prompt IS NOT NULL AND prompt <> '' AND source_slug IS NOT NULL AND source_slug <> ''
     LIMIT 1"
)->fetch();

if ($completedWithSlug) {
    [$status, , $headers] = $request('/prompts/' . (int) $completedWithSlug['id']);
    $assert($status === 301, 'Legacy numeric prompt URLs should redirect permanently when a slug exists.');
    $assert(
        ($headers['location'] ?? null) === '/prompts/' . $completedWithSlug['source_slug'],
        'Legacy numeric prompt redirect should target the canonical slug URL.'
    );
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }

    exit(1);
}

echo "HTTP SEO checks passed for {$baseUrl}.\n";
