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
$categorySlider = \App\Core\View::render('partials/category-slider', [
    'categories' => ['portrait', 'art'],
    'categoryCounts' => ['portrait' => 249, 'art' => 88],
    'categoryArtwork' => [
        'portrait' => [
            'src' => '/storage/prompts/images/portrait.webp?v=1',
            'width' => 480,
            'height' => 640,
            'webp_srcset' => '/storage/prompts/images/portrait-480w.webp?v=1 480w',
            'avif_srcset' => null,
        ],
        'art' => null,
    ],
    'activeCategory' => 'portrait',
]);
$homeCategorySlider = \App\Core\View::render('partials/category-slider', [
    'categories' => ['portrait', 'product', 'fashion'],
    'categoryCounts' => ['portrait' => 249, 'product' => 90, 'fashion' => 394],
    'categoryArtwork' => [
        'portrait' => [
            'src' => '/storage/prompts/images/portrait.webp?v=1',
            'width' => 480,
            'height' => 640,
            'webp_srcset' => null,
            'avif_srcset' => null,
        ],
    ],
    'activeCategory' => '',
    'categorySliderVariant' => 'home',
    'includeAllCategory' => false,
]);
$navigationScript = file_get_contents(base_path('public/assets/js/app.js')) ?: '';

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
$assert(str_contains($categorySlider, 'data-category-slider'), 'Library categories should render in a dedicated slider.');
$assert(str_contains($categorySlider, 'data-category-slider-previous'), 'Category slider should expose a previous control.');
$assert(str_contains($categorySlider, 'data-category-slider-next'), 'Category slider should expose a next control.');
$assert(str_contains($categorySlider, 'href="/prompts"'), 'Category slider should provide an all-prompts destination.');
$assert(
    preg_match('/class="category-slider-item is-active"[^>]+href="\/ai-prompts\/portrait"[^>]+aria-current="page"/s', $categorySlider) === 1,
    'Category slider should mark the selected category as current.'
);
$assert(str_contains($categorySlider, '249 prompts'), 'Category slider should expose the category result count.');
$assert(str_contains($categorySlider, 'loading="eager"'), 'Above-grid category artwork should load eagerly.');
$assert(
    str_contains($homeCategorySlider, 'class="category-slider home-category-slider"'),
    'Homepage categories should render with the light reference-style slider variant.'
);
$assert(
    str_contains($homeCategorySlider, 'data-category-slider-progress-thumb'),
    'Homepage category slider should expose a visual scroll-progress indicator.'
);
$assert(
    ! str_contains($homeCategorySlider, '>All prompts<')
        && str_contains($homeCategorySlider, 'href="/ai-prompts/portrait"'),
    'Homepage category slider should contain category destinations without an all-prompts card.'
);
$assert(
    str_contains($navigationScript, "viewport.scrollBy")
        && str_contains($navigationScript, "ResizeObserver")
        && str_contains($navigationScript, "progressThumb.style.width"),
    'Category slider script should support button scrolling, responsive controls, and visual progress updates.'
);

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }

    exit(1);
}

echo "Navigation checks passed: shared links, active states, mobile controls, and category slider are present.\n";
