<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\View;
use App\Services\SeoService;

$router = require dirname(__DIR__) . '/bootstrap/app.php';
require base_path('routes/web.php');

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$originalAppUrl = $_ENV['APP_URL'] ?? null;
$originalGoogleVerification = $_ENV['GOOGLE_SITE_VERIFICATION'] ?? null;
$originalBingVerification = $_ENV['BING_SITE_VERIFICATION'] ?? null;

$_ENV['APP_URL'] = 'https://mypromptart.com';
$_ENV['GOOGLE_SITE_VERIFICATION'] = 'google-test-token';
$_ENV['BING_SITE_VERIFICATION'] = 'bing-test-token';

$assert(
    SeoService::listingUrl('/prompts', 1) === 'https://mypromptart.com/prompts',
    'Page one canonical should omit the page query.'
);
$assert(
    SeoService::listingUrl('/prompts', 3) === 'https://mypromptart.com/prompts?page=3',
    'Paginated canonical should preserve the page number.'
);
$assert(
    SeoService::categoryUrl('portrait') === 'https://mypromptart.com/prompts/category/portrait',
    'Category canonical should use the clean category route.'
);

$request = new Request('GET', '/prompts', [], [], [], [
    'HTTP_HOST' => 'www.mypromptart.com',
    'HTTP_X_FORWARDED_PROTO' => 'http',
    'REQUEST_URI' => '/prompts/?page=2',
]);
$assert(
    SeoService::canonicalRedirectUrl($request) === 'https://mypromptart.com/prompts?page=2',
    'Canonical redirect should normalize scheme, host, and trailing slash.'
);

$wwwRequest = new Request('HEAD', '/prompts/studio-portrait', [], [], [], [
    'HTTP_HOST' => 'www.mypromptart.com',
    'HTTPS' => 'on',
    'REQUEST_URI' => '/prompts/studio-portrait?ref=legacy',
]);
$assert(
    SeoService::canonicalRedirectUrl($wwwRequest) === 'https://mypromptart.com/prompts/studio-portrait?ref=legacy',
    'HTTPS www requests should permanently consolidate on the non-www origin without losing the path or query.'
);

$canonicalRequest = new Request('GET', '/prompts/studio-portrait', [], [], [], [
    'HTTP_HOST' => 'mypromptart.com',
    'HTTPS' => 'on',
    'REQUEST_URI' => '/prompts/studio-portrait',
]);
$assert(
    SeoService::canonicalRedirectUrl($canonicalRequest) === null,
    'A clean request on the canonical HTTPS origin must not redirect.'
);

$apacheRules = file_get_contents(public_path('.htaccess'));
$assert(
    is_string($apacheRules)
        && str_contains($apacheRules, 'RewriteCond %{HTTP_HOST} ^www\.mypromptart\.com$ [NC]')
        && str_contains($apacheRules, 'RewriteRule ^ https://mypromptart.com%{REQUEST_URI} [R=301,L,NE]'),
    'Apache should redirect the www host to the canonical non-www HTTPS origin before front-controller routing.'
);

$prompt = [
    'id' => 7,
    'source_slug' => 'studio-portrait',
    'title' => 'Studio portrait',
    'prompt' => 'A carefully lit studio portrait.',
    'category' => 'portrait',
    'thumbnail_path' => null,
    'generated_at' => '2026-08-01 10:00:00',
    'created_at' => '2026-08-01 09:00:00',
    'updated_at' => '2026-08-02 10:00:00',
];
$collection = SeoService::collectionSchema(
    'Portrait prompts',
    'Completed portrait prompts.',
    SeoService::categoryUrl('portrait'),
    1,
    [$prompt]
);
$assert(($collection['mainEntity']['numberOfItems'] ?? null) === 1, 'Collection schema should expose its result count.');
$assert(
    ($collection['mainEntity']['itemListElement'][0]['url'] ?? null) === 'https://mypromptart.com/prompts/studio-portrait',
    'Collection schema should list visible prompt URLs.'
);

$promptSchema = SeoService::promptSchema($prompt);
$assert(($promptSchema['@type'] ?? null) === 'CreativeWork', 'Prompt schema should use CreativeWork.');
$assert(isset($promptSchema['datePublished'], $promptSchema['dateModified']), 'Prompt schema should include backed publication dates.');
$assert(! isset($promptSchema['aggregateRating']), 'Prompt schema must not invent ratings.');

$html = View::render('public/404', [
    'title' => 'Page not found',
    'metaTitle' => 'Page Not Found | MyPromptArt',
    'metaDescription' => 'The requested page was not found.',
    'canonical' => 'https://mypromptart.com/missing',
    'noindex' => true,
    'nofollow' => true,
    'showAds' => false,
], 'layouts/public');
$assert(str_contains($html, 'content="noindex,nofollow"'), 'Error pages should render noindex,nofollow.');
$assert(! str_contains($html, 'name="keywords"'), 'Public layout should not render meta keywords.');
$assert(str_contains($html, 'google-site-verification'), 'Google verification should be environment-driven.');
$assert(str_contains($html, 'msvalidate.01'), 'Bing verification should be environment-driven.');
$assert(str_contains($html, 'rel="canonical" href="https://mypromptart.com/missing"'), 'Error page canonical should be escaped and absolute.');
$assert(str_contains($html, 'assets/img/my-prompt-art-logo.webp'), 'Public header should render the MyPromptArt logo asset.');
$assert(! str_contains($html, '<span class="brand-mark">PL</span>'), 'Public header should not render the legacy PL badge.');
$assert(str_contains($html, 'rel="icon"') && str_contains($html, '/favicon.png"'), 'Public pages should render the PNG favicon.');
$assert(str_contains($html, 'rel="apple-touch-icon"') && str_contains($html, '/apple-touch-icon.png"'), 'Public pages should render the Apple touch icon.');

$headerLogoSize = getimagesize(public_path('assets/img/my-prompt-art-logo.webp'));
$headerLogoBytes = file_get_contents(public_path('assets/img/my-prompt-art-logo.webp'));
$faviconSize = getimagesize(public_path('favicon.png'));
$assert(
    is_array($headerLogoSize) && $headerLogoSize[0] === 1200 && $headerLogoSize[1] === 337 && ($headerLogoSize['mime'] ?? null) === 'image/webp',
    'Header logo should be the optimized 1200x337 WebP asset.'
);
$assert(
    is_string($headerLogoBytes) && str_contains($headerLogoBytes, 'ALPH'),
    'Header logo WebP should include an alpha channel.'
);
$assert(
    is_array($faviconSize) && $faviconSize[0] === 512 && $faviconSize[1] === 512 && ($faviconSize['mime'] ?? null) === 'image/png',
    'Favicon should be a square 512x512 PNG asset.'
);

$notFoundRequest = new Request('GET', '/definitely-missing', [], [], [], [
    'HTTP_HOST' => 'mypromptart.com',
    'HTTP_X_FORWARDED_PROTO' => 'https',
    'REQUEST_URI' => '/definitely-missing',
]);
$notFound = $router->dispatch($notFoundRequest);
$assert($notFound->status() === 404, 'Unknown routes should return HTTP 404.');
$assert(($notFound->headers()['X-Robots-Tag'] ?? null) === 'noindex, nofollow', 'Unknown routes should send X-Robots-Tag.');

if ($originalAppUrl === null) {
    unset($_ENV['APP_URL']);
} else {
    $_ENV['APP_URL'] = $originalAppUrl;
}

foreach ([
    'GOOGLE_SITE_VERIFICATION' => $originalGoogleVerification,
    'BING_SITE_VERIFICATION' => $originalBingVerification,
] as $key => $value) {
    if ($value === null) {
        unset($_ENV[$key]);
    } else {
        $_ENV[$key] = $value;
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }

    exit(1);
}

echo "SEO unit checks passed.\n";
