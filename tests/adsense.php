<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\View;
use App\Services\AdSenseService;
use App\Services\SeoService;

$router = require dirname(__DIR__) . '/bootstrap/app.php';
require base_path('routes/web.php');

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$envKeys = [
    'ADSENSE_ENABLED',
    'ADSENSE_PUBLISHER_ID',
    'ADSENSE_HOME_SLOT',
    'ADSENSE_LIBRARY_SLOT',
    'ADSENSE_DETAIL_SLOT',
];
$originalEnvironment = [];

foreach ($envKeys as $key) {
    $originalEnvironment[$key] = [
        'exists' => array_key_exists($key, $_ENV),
        'value' => $_ENV[$key] ?? null,
        'process' => getenv($key),
    ];
}

$setEnv = static function (string $key, string $value): void {
    $_ENV[$key] = $value;
    putenv($key . '=' . $value);
};

$renderPublicPage = static function (bool $showAds, ?string $placement): string {
    return View::render('public/privacy', [
        'title' => 'Privacy',
        'metaTitle' => 'Privacy | MyPromptArt',
        'metaDescription' => 'Privacy information.',
        'canonical' => 'https://mypromptart.com/privacy-policy',
        'showAds' => $showAds,
        'adPlacement' => $placement,
    ], 'layouts/public');
};

$request = static function (string $path): Request {
    return new Request('GET', $path, [], [], [], [
        'HTTP_HOST' => 'mypromptart.com',
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'REQUEST_URI' => $path,
    ]);
};

$assert(
    AdSenseService::normalizePublisherId('pub-9410767301492911') === 'pub-9410767301492911',
    'Publisher IDs should retain their pub- form.'
);
$assert(
    AdSenseService::normalizePublisherId('ca-pub-9410767301492911') === 'pub-9410767301492911',
    'Client IDs should normalize to the pub- seller form.'
);
$assert(
    AdSenseService::normalizeClientId('pub-9410767301492911') === 'ca-pub-9410767301492911',
    'Publisher IDs should normalize to the ca-pub- client form.'
);
$assert(
    AdSenseService::normalizeClientId('ca-pub-9410767301492911') === 'ca-pub-9410767301492911',
    'Client IDs should retain their ca-pub- form.'
);

foreach (['', 'pub-123', 'ca-pub-941076730149291', 'pub-941076730149291x', 'google.com, pub-9410767301492911'] as $invalidId) {
    $assert(AdSenseService::normalizePublisherId($invalidId) === null, "Malformed publisher ID should be rejected: {$invalidId}");
}

$setEnv('ADSENSE_ENABLED', 'true');
$setEnv('ADSENSE_PUBLISHER_ID', 'pub-9410767301492911');
$setEnv('ADSENSE_HOME_SLOT', '');
$setEnv('ADSENSE_LIBRARY_SLOT', '0000000000');
$setEnv('ADSENSE_DETAIL_SLOT', 'not-a-slot');

$config = AdSenseService::configuration(true, 'home');
$assert($config['enabled'] === true, 'Valid enabled AdSense configuration should be active.');
$assert($config['loader_enabled'] === true, 'Eligible public content should enable the site-level loader.');
$assert($config['publisher_id'] === 'pub-9410767301492911', 'Configuration should expose only the normalized publisher ID.');
$assert($config['client_id'] === 'ca-pub-9410767301492911', 'Configuration should expose the normalized client ID.');
$assert($config['slot_id'] === null, 'An empty manual placement must not produce an ad unit.');
$assert(AdSenseService::slotId('library') === null, 'The all-zero placeholder slot must be rejected.');
$assert(AdSenseService::slotId('detail') === null, 'Non-numeric slots must be rejected.');
$assert(AdSenseService::slotId('unknown') === null, 'Unknown placements must not read arbitrary environment values.');
$assert(SeoService::canShowAds(false, 1), 'Eligible content should allow ads when configuration is enabled.');
$assert(! SeoService::canShowAds(true, 1), 'Noindex pages must not allow ads.');
$assert(! SeoService::canShowAds(false, 0), 'Empty result pages must not allow ads.');

$html = $renderPublicPage(SeoService::canShowAds(false, 1), 'home');
$loaderUrl = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9410767301492911';
$assert(
    str_contains($html, '<meta name="google-adsense-account" content="ca-pub-9410767301492911">'),
    'Configured public pages should expose the official AdSense account meta tag.'
);
$assert(substr_count($html, $loaderUrl) === 1, 'Eligible pages should render exactly one normalized AdSense loader.');
$assert(! str_contains($html, '<ins class="adsbygoogle"'), 'No manual ad unit should render without a configured slot.');
$assert(! str_contains($html, '0000000000'), 'The fake slot value must never appear in rendered markup.');
$assert(! str_contains($html, 'client=pub-9410767301492911'), 'The JavaScript loader must never use the pub- seller form.');

$setEnv('ADSENSE_HOME_SLOT', '1234567890');
$htmlWithSlot = $renderPublicPage(SeoService::canShowAds(false, 1), 'home');
$assert(substr_count($htmlWithSlot, $loaderUrl) === 1, 'A manual unit must not duplicate the site-level loader.');
$assert(str_contains($htmlWithSlot, '<ins class="adsbygoogle"'), 'A valid configured slot should render a manual ad unit.');
$assert(str_contains($htmlWithSlot, 'data-ad-client="ca-pub-9410767301492911"'), 'Manual units should use the ca-pub- client form.');
$assert(str_contains($htmlWithSlot, 'data-ad-slot="1234567890"'), 'Manual units should use the configured placement slot.');
$assert(str_contains($htmlWithSlot, 'aria-label="Advertisement"'), 'Manual units should have a clear accessible advertisement label.');

$privacyResponse = $router->dispatch($request('/privacy-policy'));
$assert($privacyResponse->status() === 200, 'Privacy page should remain public.');
$assert(substr_count($privacyResponse->body(), $loaderUrl) === 1, 'Indexable trust pages should render exactly one AdSense loader.');
$assert(! str_contains($privacyResponse->body(), '<ins class="adsbygoogle"'), 'Trust pages should not render manual ad units.');
$assert(
    str_contains($privacyResponse->body(), '<meta name="google-adsense-account" content="ca-pub-9410767301492911">'),
    'The public layout should expose the account verification meta tag.'
);

$loginResponse = $router->dispatch($request('/login'));
$assert(! str_contains($loginResponse->body(), 'google-adsense-account'), 'Authentication pages must not expose AdSense markup.');
$assert(! str_contains($loginResponse->body(), 'pagead2.googlesyndication.com'), 'Authentication pages must not load AdSense.');

$setEnv('ADSENSE_ENABLED', 'false');
$disabledHtml = $renderPublicPage(SeoService::canShowAds(false, 1), 'home');
$assert(str_contains($disabledHtml, 'google-adsense-account'), 'A valid account meta tag should remain available while ad serving is disabled.');
$assert(! str_contains($disabledHtml, 'pagead2.googlesyndication.com'), 'Disabled configuration must not load AdSense.');
$assert(! str_contains($disabledHtml, '<ins class="adsbygoogle"'), 'Disabled configuration must not render manual ad units.');

$adsResponse = $router->dispatch($request('/ads.txt'));
$assert($adsResponse->status() === 200, 'ads.txt should return HTTP 200.');
$assert(
    ($adsResponse->headers()['Content-Type'] ?? null) === 'text/plain; charset=UTF-8',
    'ads.txt should use the plain-text UTF-8 content type.'
);
$assert(
    ($adsResponse->headers()['Cache-Control'] ?? null) === 'public, max-age=3600',
    'ads.txt should use a short public cache policy.'
);
$assert(
    $adsResponse->body() === "google.com, pub-9410767301492911, DIRECT, f08c47fec0942fa0\n",
    'ads.txt should contain exactly one normalized Google seller record and a trailing newline.'
);
$assert(! str_contains($adsResponse->body(), 'ca-pub-'), 'ads.txt must never contain the ca-pub- client form.');

$setEnv('ADSENSE_ENABLED', 'true');
$setEnv('ADSENSE_PUBLISHER_ID', 'invalid-publisher');
$invalidConfig = AdSenseService::configuration(true, 'home');
$assert($invalidConfig['enabled'] === false, 'Malformed publisher configuration should be disabled.');
$assert($invalidConfig['publisher_id'] === null && $invalidConfig['client_id'] === null, 'Malformed IDs should not leak into configuration.');
$invalidHtml = $renderPublicPage(true, 'home');
$assert(! str_contains($invalidHtml, 'google-adsense-account'), 'Malformed publisher IDs must not render a verification meta tag.');
$assert(! str_contains($invalidHtml, 'pagead2.googlesyndication.com'), 'Malformed publisher IDs must not load AdSense.');
$assert($router->dispatch($request('/ads.txt'))->body() === '', 'Malformed publisher IDs should produce a safe empty ads.txt response.');

foreach ($originalEnvironment as $key => $state) {
    if ($state['exists']) {
        $_ENV[$key] = $state['value'];
    } else {
        unset($_ENV[$key]);
    }

    if ($state['process'] === false) {
        putenv($key);
    } else {
        putenv($key . '=' . $state['process']);
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }

    exit(1);
}

echo "AdSense checks passed.\n";
