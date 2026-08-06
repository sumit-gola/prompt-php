<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\Prompt;

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
$assertJsonLd($home, 'Homepage');

[$status, $library] = $request('/prompts');
$assert($status === 200, 'Library should return HTTP 200.');
$assert($canonical($library) === $baseUrl . '/prompts', 'Library should have a clean canonical.');
$assert($meta($library, 'robots') === 'index,follow', 'Clean library should be indexable.');
$assert(! str_contains($library, 'name="keywords"'), 'Library should not render meta keywords.');

if (str_contains($library, '?page=2')) {
    [$status, $pageTwo] = $request('/prompts?page=2');
    $assert($status === 200, 'Valid page two should return HTTP 200.');
    $assert($canonical($pageTwo) === $baseUrl . '/prompts?page=2', 'Page two should be self-canonical.');
}

[$status, $emptySearch] = $request('/prompts?q=seo-smoke-no-result-3f92d0a1');
$assert($status === 200, 'Empty search should return HTTP 200.');
$assert($meta($emptySearch, 'robots') === 'noindex,follow', 'Search results should be noindex,follow.');
$assert(! str_contains($emptySearch, 'pagead2.googlesyndication.com'), 'Empty noindex results should not render AdSense.');

preg_match('#href=["\'](/prompts/category/[a-z]+)["\']#', $home, $categoryMatch);
$categoryPath = $categoryMatch[1] ?? '/prompts/category/portrait';
[$status, $category] = $request($categoryPath);
$assert($status === 200, 'A populated category page should return HTTP 200.');
$assert($canonical($category) === $baseUrl . $categoryPath, 'Category page should be self-canonical.');
$assert(str_contains($category, 'aria-label="Breadcrumb"'), 'Category page should render visible breadcrumbs.');
$assertJsonLd($category, 'Category page');

[$status, $queryCategory] = $request('/prompts?category=' . rawurlencode(basename($categoryPath)));
$assert($status === 200, 'Legacy query-string category filtering should keep working.');
$assert($meta($queryCategory, 'robots') === 'noindex,follow', 'Query-string category duplicates should be noindex,follow.');
$assert($canonical($queryCategory) === $baseUrl . $categoryPath, 'Query-string category should canonicalize to its clean category page.');

[$status, $robots] = $request('/robots.txt');
$assert($status === 200, 'robots.txt should return HTTP 200.');
$assert(str_contains($robots, 'Sitemap: ' . $baseUrl . '/sitemap.xml'), 'robots.txt should advertise the canonical sitemap.');

[$status, $sitemap] = $request('/sitemap.xml');
$assert($status === 200, 'sitemap.xml should return HTTP 200.');
$assert(str_starts_with(ltrim($sitemap), '<?xml version="1.0" encoding="UTF-8"?>'), 'Sitemap should be UTF-8 XML.');
$assert(str_contains($sitemap, $baseUrl . '/prompts/category/'), 'Sitemap should include populated category pages.');

[$chunkStatus, $promptChunk] = $request('/sitemaps/prompts-1.xml');
$assert($chunkStatus === 200, 'Prompt sitemap chunks should be routable.');
$assert(str_contains($promptChunk, '<urlset'), 'Prompt sitemap chunk should be a URL set.');

if (function_exists('simplexml_load_string')) {
    $assert(simplexml_load_string($sitemap) !== false, 'Sitemap XML should parse successfully.');
}

preg_match('#<loc>(' . preg_quote($baseUrl, '#') . '/prompts/(?!category/)[^<]+)</loc>#', $sitemap, $promptMatch);
$promptUrl = html_entity_decode((string) ($promptMatch[1] ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');

if ($promptUrl !== '') {
    $promptPath = (string) parse_url($promptUrl, PHP_URL_PATH);
    [$status, $promptPage] = $request($promptPath, 'facebookexternalhit/1.1');
    $assert($status === 200, 'A sitemap prompt should return HTTP 200.');
    $assert($canonical($promptPage) === $promptUrl, 'Prompt detail should be self-canonical.');
    $assert($meta($promptPage, 'og:image', true) !== null, 'Prompt detail should render an Open Graph image.');
    $assert(str_contains($promptPage, 'aria-label="Breadcrumb"'), 'Prompt detail should render visible breadcrumbs.');
    $assert(str_contains($promptPage, 'AI image prompt preview'), 'Prompt preview should have descriptive alt text.');
    $assertJsonLd($promptPage, 'Prompt detail');
}

[$status, $missing, $headers] = $request('/prompts/seo-smoke-definitely-missing');
$assert($status === 404, 'Missing prompt should return HTTP 404.');
$assert($meta($missing, 'robots') === 'noindex,nofollow', 'Missing prompt should render noindex,nofollow.');
$assert(($headers['x-robots-tag'] ?? null) === 'noindex, nofollow', 'Missing prompt should send X-Robots-Tag.');

[$status, $login, $headers] = $request('/login');
$assert($status === 200, 'Login page should return HTTP 200.');
$assert($meta($login, 'robots') === 'noindex,nofollow', 'Login page should render noindex,nofollow.');
$assert(($headers['x-robots-tag'] ?? null) === 'noindex, nofollow', 'Login page should send X-Robots-Tag.');

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
