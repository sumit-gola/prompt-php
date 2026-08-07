<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$failures = [];

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$renderHeader = static function (string $path): string {
    $previousUri = $_SERVER['REQUEST_URI'] ?? null;
    $_SERVER['REQUEST_URI'] = $path;
    $siteName = \App\Services\SeoService::siteName();

    ob_start();
    require base_path('resources/views/partials/site-header.php');
    $html = (string) ob_get_clean();

    if ($previousUri === null) {
        unset($_SERVER['REQUEST_URI']);
    } else {
        $_SERVER['REQUEST_URI'] = $previousUri;
    }

    return $html;
};

$homeHeader = $renderHeader('/');
$aboutHeader = $renderHeader('/about');
$libraryHeader = $renderHeader('/prompts?q=portrait');
$categoryHeader = $renderHeader('/ai-prompts/portrait');

$assert(str_contains($homeHeader, 'href="#explore"'), 'Homepage Explore link should target its local section.');
$assert(str_contains($homeHeader, 'href="#categories"'), 'Homepage Categories link should target its local section.');
$assert(
    preg_match('/class="is-active"\s+href="#explore"[^>]+aria-current="location"/s', $homeHeader) === 1,
    'Homepage Explore link should be marked as the current location.'
);
$assert(str_contains($aboutHeader, 'href="/#explore"'), 'Inner-page Explore link should return to the homepage section.');
$assert(str_contains($aboutHeader, 'href="/#categories"'), 'Inner-page Categories link should return to the homepage section.');
$assert(
    preg_match('/class="is-active"\s+href="\/about"\s+aria-current="page"/s', $aboutHeader) === 1,
    'About navigation item should be active on the About page.'
);
$assert(
    preg_match('/class="button button-small is-active"\s+href="\/prompts"\s+aria-current="page"/s', $libraryHeader) === 1,
    'Browse prompts action should be active on the library page.'
);
$assert(
    preg_match('/class="button button-small is-active"\s+href="\/prompts"\s+aria-current="page"/s', $categoryHeader) === 1,
    'Browse prompts action should be active on category pages.'
);
$assert(str_contains($homeHeader, 'data-site-nav-toggle'), 'Shared header should render the mobile navigation toggle.');
$assert(str_contains($homeHeader, 'aria-controls="site-navigation"'), 'Mobile toggle should identify the controlled menu.');
$assert(str_contains($homeHeader, 'data-site-nav-menu'), 'Shared header should render the responsive menu container.');

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }

    exit(1);
}

echo "Navigation checks passed: shared links, active states, and mobile controls are present.\n";
