<?php

declare(strict_types=1);

use App\Controllers\PublicController;
use App\Core\View;
use App\Services\PromptImageService;

require dirname(__DIR__) . '/bootstrap/app.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
    fwrite(STDERR, "FAIL: GD with WebP support is required.\n");
    exit(1);
}

$testRoot = sys_get_temp_dir() . '/mypromptart-image-seo-' . bin2hex(random_bytes(6));
$sourceDirectory = $testRoot . '/storage/prompts/2026/08';
mkdir($sourceDirectory, 0755, true);

$storedSourcePath = 'prompts/2026/08/4b19c328-1913-4317-a359-fe5cb3dec87d.jpg';
$sourceRelativePath = 'storage/' . $storedSourcePath;
$sourceAbsolutePath = $testRoot . '/' . $sourceRelativePath;
$source = imagecreatetruecolor(1200, 800);
$background = imagecolorallocate($source, 15, 45, 80);
$accent = imagecolorallocate($source, 50, 130, 210);
imagefill($source, 0, 0, $background);
imagefilledellipse($source, 600, 400, 720, 520, $accent);
imagejpeg($source, $sourceAbsolutePath, 90);
imagedestroy($source);

$originalPublicPath = $_ENV['PUBLIC_PATH'] ?? null;
$originalAppUrl = $_ENV['APP_URL'] ?? null;
$_ENV['PUBLIC_PATH'] = $testRoot;
$_ENV['APP_URL'] = 'https://mypromptart.com';

$prompt = [
    'id' => 1074,
    'title' => 'Double Exposure Watercolor Portrait with Blue Ink Splash Effect',
    'source_slug' => 'double-exposure-watercolor-portrait-with-blue-ink-splash-effect',
    'category' => 'portrait',
    'thumbnail_path' => $storedSourcePath,
    'reference_image_path' => $storedSourcePath,
    'copy_count' => 4,
    'prompt' => 'Create a double exposure watercolor portrait with a navy blue ink splash effect.',
];

$image = PromptImageService::optimize($prompt, null, true);
$expectedStem = 'storage/prompts/images/double-exposure-watercolor-portrait-with-blue-ink-splash-effect';

$assert($image !== null, 'Image optimization should return metadata.');
$assert(($image['path'] ?? null) === $expectedStem . '.webp', 'The canonical image should use a descriptive WebP filename.');
$assert(is_file($testRoot . '/' . $expectedStem . '.webp'), 'The full-size WebP image should be generated.');
$assert(is_file($testRoot . '/' . $expectedStem . '-480w.webp'), 'The 480px WebP variant should be generated.');
$assert(is_file($testRoot . '/' . $expectedStem . '-960w.webp'), 'The 960px WebP variant should be generated.');
$assert(($image['width'] ?? null) === 1200 && ($image['height'] ?? null) === 800, 'Image metadata should expose explicit intrinsic dimensions.');
$assert(($image['alt'] ?? null) === $prompt['title'], 'Alt text should describe the image using its prompt title.');
$assert(preg_match('/-480w\.webp\?v=\d+ 480w/', (string) ($image['webp_srcset'] ?? '')) === 1, 'Responsive metadata should include the 480px WebP candidate.');
$assert(preg_match('/-960w\.webp\?v=\d+ 960w/', (string) ($image['webp_srcset'] ?? '')) === 1, 'Responsive metadata should include the 960px WebP candidate.');
$assert(! str_contains((string) ($image['url'] ?? ''), '4b19c328-1913-4317-a359-fe5cb3dec87d'), 'Crawler-facing image URLs should not expose UUID filenames.');

if (function_exists('imageavif')) {
    unlink($testRoot . '/' . $expectedStem . '-480w.avif');
    $resumedImage = PromptImageService::optimize($prompt);
    $assert($resumedImage !== null && is_file($testRoot . '/' . $expectedStem . '-480w.avif'), 'Optimization should resume a missing AVIF derivative when the canonical WebP already exists.');
}

if (function_exists('imageavif')) {
    $assert(is_file($testRoot . '/' . $expectedStem . '.avif'), 'The full-size AVIF image should be generated when GD supports AVIF.');
    $assert(preg_match('/-480w\.avif\?v=\d+ 480w/', (string) ($image['avif_srcset'] ?? '')) === 1, 'Responsive metadata should advertise AVIF candidates when available.');
}

$responsiveHtml = View::render('partials/responsive-image', [
    'image' => $image,
    'imageLoading' => 'lazy',
    'imageFetchPriority' => 'auto',
    'imageSizes' => '(max-width: 680px) 100vw, 25vw',
]);
$assert(str_contains($responsiveHtml, '<picture>'), 'Responsive image markup should use a picture element.');
$assert(str_contains($responsiveHtml, 'srcset='), 'Responsive image markup should include srcset.');
$assert(str_contains($responsiveHtml, 'width="1200"') && str_contains($responsiveHtml, 'height="800"'), 'Responsive image markup should include explicit dimensions.');
$assert(str_contains($responsiveHtml, 'loading="lazy"'), 'Below-the-fold image markup should use lazy loading.');
$assert(str_contains($responsiveHtml, 'alt="Double Exposure Watercolor Portrait with Blue Ink Splash Effect"'), 'Responsive image markup should render descriptive alt text.');

$cardHtml = View::render('partials/prompt-card', ['prompt' => $prompt]);
$assert(str_contains($cardHtml, $expectedStem . '.webp?v='), 'Prompt cards should render the versioned descriptive canonical image.');
$assert(str_contains($cardHtml, 'loading="lazy"'), 'Prompt card images should always lazy load.');
$assert(! str_contains($cardHtml, '4b19c328-1913-4317-a359-fe5cb3dec87d'), 'Prompt card markup should not expose the UUID source filename.');
$assert(str_contains($cardHtml, 'prompt-gallery-card'), 'Prompt cards should render the image-first gallery design.');
$assert(str_contains($cardHtml, 'prompt-card-copy copy-button'), 'Gallery cards should expose a compact copy action.');
$assert(str_contains($cardHtml, 'data-copy-label'), 'Gallery copy actions should provide an accessible live label.');

$detailHtml = View::render('prompts/show', [
    'prompt' => $prompt,
    'promptImage' => $image,
    'related' => [],
    'styleNotes' => [],
    'breadcrumbs' => [
        ['name' => 'MyPromptArt', 'url' => 'https://mypromptart.com/'],
        ['name' => 'Portrait AI Prompts', 'url' => 'https://mypromptart.com/ai-prompts/portrait'],
        ['name' => $prompt['title'], 'url' => 'https://mypromptart.com/prompts/' . $prompt['source_slug']],
    ],
    'categoryName' => 'Portrait',
    'categoryPath' => '/ai-prompts/portrait',
    'publishedAt' => null,
    'modifiedAt' => null,
    'reviewedAt' => null,
    'testedModels' => '',
    'sourceUrl' => null,
    'sourceLabel' => '',
]);
$assert(str_contains($detailHtml, '<figcaption>'), 'Prompt detail images should include a visible caption.');
$assert(str_contains($detailHtml, 'Open full-size image:'), 'Prompt detail images should link to a full-size preview.');
$assert(str_contains($detailHtml, 'loading="eager"') && str_contains($detailHtml, 'fetchpriority="high"'), 'The above-the-fold detail image should load eagerly with high priority.');

$controller = new PublicController();
$method = new ReflectionMethod($controller, 'urlSetResponse');
$method->setAccessible(true);
$response = $method->invoke($controller, [[
    'loc' => 'https://mypromptart.com/prompts/' . $prompt['source_slug'],
    'lastmod' => '2026-08-07',
    'image' => $image['url'],
]]);
$assert(str_contains($response->body(), 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"'), 'Sitemaps should declare the Google image namespace.');
$assert(str_contains($response->body(), '<image:loc>https://mypromptart.com/' . $expectedStem . '.webp?v='), 'Sitemaps should include the versioned descriptive canonical image URL.');
$publicControllerSource = file_get_contents(base_path('app/Controllers/PublicController.php')) ?: '';
$assert(str_contains($publicControllerSource, 'PromptImageService::metadata($prompt, false)'), 'Sitemap rendering should not generate image derivatives synchronously.');

$removeTree = static function (string $path) use (&$removeTree): void {
    if (is_dir($path)) {
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $removeTree($path . '/' . $entry);
        }
        rmdir($path);
        return;
    }

    if (is_file($path)) {
        unlink($path);
    }
};
$removeTree($testRoot);

if ($originalPublicPath === null) {
    unset($_ENV['PUBLIC_PATH']);
} else {
    $_ENV['PUBLIC_PATH'] = $originalPublicPath;
}

if ($originalAppUrl === null) {
    unset($_ENV['APP_URL']);
} else {
    $_ENV['APP_URL'] = $originalAppUrl;
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }
    exit(1);
}

echo "Image SEO checks passed.\n";
