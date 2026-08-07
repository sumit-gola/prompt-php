<?php

declare(strict_types=1);

use App\Core\View;
use App\Services\GoogleTagManagerService;

require dirname(__DIR__) . '/bootstrap/app.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$originalEnvironment = [
    'exists' => array_key_exists('GTM_CONTAINER_ID', $_ENV),
    'value' => $_ENV['GTM_CONTAINER_ID'] ?? null,
    'process' => getenv('GTM_CONTAINER_ID'),
];

$setContainerId = static function (string $value): void {
    $_ENV['GTM_CONTAINER_ID'] = $value;
    putenv('GTM_CONTAINER_ID=' . $value);
};

$assert(
    GoogleTagManagerService::normalizeContainerId(' gtm-kg53q7r5 ') === 'GTM-KG53Q7R5',
    'GTM container IDs should be trimmed and normalized to uppercase.'
);

foreach (['', 'G-ABC123', 'GTM-', 'GTM-ABC 123', 'GTM-ABC<script>'] as $invalidId) {
    $assert(
        GoogleTagManagerService::normalizeContainerId($invalidId) === null,
        "Malformed GTM container ID should be rejected: {$invalidId}"
    );
}

$setContainerId('GTM-KG53Q7R5');

$pages = [
    'public' => View::render('public/privacy', ['title' => 'Privacy'], 'layouts/public'),
    'authentication' => View::render('auth/login', ['title' => 'Sign in'], 'layouts/auth'),
    'admin' => View::render('admin/404', ['title' => 'Not found'], 'layouts/admin'),
];

foreach ($pages as $label => $html) {
    $headScript = 'https://www.googletagmanager.com/gtm.js?id=';
    $bodyFallback = 'https://www.googletagmanager.com/ns.html?id=GTM-KG53Q7R5';
    $headPosition = strpos($html, '<head>');
    $scriptPosition = strpos($html, '<!-- Google Tag Manager -->');
    $metaPosition = strpos($html, '<meta charset="utf-8">');
    $bodyPosition = strpos($html, '<body');
    $noscriptPosition = strpos($html, '<!-- Google Tag Manager (noscript) -->');

    $assert(substr_count($html, $headScript) === 1, "{$label} layout should render one GTM loader.");
    $assert(substr_count($html, $bodyFallback) === 1, "{$label} layout should render one GTM noscript fallback.");
    $assert(
        is_int($headPosition) && is_int($scriptPosition) && is_int($metaPosition)
            && $headPosition < $scriptPosition && $scriptPosition < $metaPosition,
        "{$label} layout should place GTM before regular head metadata."
    );
    $assert(
        is_int($bodyPosition) && is_int($noscriptPosition) && $bodyPosition < $noscriptPosition,
        "{$label} layout should place the GTM noscript fallback immediately after body opens."
    );
}

$setContainerId('not-a-container');
$disabledHtml = View::render('public/privacy', ['title' => 'Privacy'], 'layouts/public');
$assert(
    ! str_contains($disabledHtml, 'googletagmanager.com'),
    'Invalid GTM configuration must not render tracking markup.'
);

if ($originalEnvironment['exists']) {
    $_ENV['GTM_CONTAINER_ID'] = $originalEnvironment['value'];
} else {
    unset($_ENV['GTM_CONTAINER_ID']);
}

if ($originalEnvironment['process'] === false) {
    putenv('GTM_CONTAINER_ID');
} else {
    putenv('GTM_CONTAINER_ID=' . $originalEnvironment['process']);
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }

    exit(1);
}

echo "Google Tag Manager checks passed.\n";
