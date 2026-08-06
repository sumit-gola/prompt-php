<?php
$pageTitle = $metaTitle ?? $title ?? 'Prompt Library';
$description = $metaDescription ?? 'Browse and copy curated AI image prompts.';
$canonicalUrl = $canonical ?? app_url($_SERVER['REQUEST_URI'] ?? '/');
$robots = ! empty($noindex) ? 'noindex,follow' : 'index,follow';
$keywords = $metaKeywords ?? 'AI prompts, image prompts, prompt library, prompt engineering, generative AI';
$siteName = \App\Services\SeoService::siteName();
$ogType = $ogType ?? 'website';
$providedOgImage = ! empty($ogImage);
$shareImage = $providedOgImage ? $ogImage : \App\Services\SeoService::defaultShareImageUrl();
$shareImageAlt = $ogImageAlt ?? \App\Services\SeoService::defaultShareImageAlt();
$shareImagePath = (string) parse_url((string) $shareImage, PHP_URL_PATH);
$shareImageType = $ogImageType ?? match (true) {
    preg_match('/\.jpe?g$/i', $shareImagePath) === 1 => 'image/jpeg',
    preg_match('/\.webp$/i', $shareImagePath) === 1 => 'image/webp',
    default => 'image/png',
};
$shareImageWidth = $ogImageWidth ?? ($providedOgImage ? null : 1200);
$shareImageHeight = $ogImageHeight ?? ($providedOgImage ? null : 630);
$shareImageFile = public_path(ltrim($shareImagePath, '/'));
$ogUpdatedTime = $ogUpdatedTime ?? (is_file($shareImageFile) ? date(DATE_ATOM, (int) filemtime($shareImageFile)) : date(DATE_ATOM));
$twitterCard = 'summary_large_image';
$structuredData = $structuredData ?? [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta name="keywords" content="<?= e($keywords) ?>">
    <meta name="robots" content="<?= e($robots) ?>">
    <meta name="googlebot" content="<?= e($robots) ?>,max-snippet:-1,max-image-preview:large,max-video-preview:-1">
    <meta name="application-name" content="<?= e($siteName) ?>">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="format-detection" content="telephone=no">
    <meta name="color-scheme" content="light">
    <meta itemprop="name" content="<?= e($pageTitle) ?>">
    <meta itemprop="description" content="<?= e($description) ?>">
    <meta itemprop="image" content="<?= e($shareImage) ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <link rel="icon" href="<?= asset('favicon.png') ?>" type="image/png" sizes="512x512">
    <link rel="apple-touch-icon" href="<?= asset('apple-touch-icon.png') ?>" sizes="180x180">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:locale" content="en_US">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:type" content="<?= e($ogType) ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta property="og:image" content="<?= e($shareImage) ?>">
    <meta property="og:image:url" content="<?= e($shareImage) ?>">
    <meta property="og:image:secure_url" content="<?= e($shareImage) ?>">
    <meta property="og:image:type" content="<?= e($shareImageType) ?>">
    <?php if ($shareImageWidth !== null && $shareImageHeight !== null): ?>
        <meta property="og:image:width" content="<?= (int) $shareImageWidth ?>">
        <meta property="og:image:height" content="<?= (int) $shareImageHeight ?>">
    <?php endif; ?>
    <meta property="og:image:alt" content="<?= e($shareImageAlt) ?>">
    <meta property="og:updated_time" content="<?= e($ogUpdatedTime) ?>">
    <meta name="twitter:card" content="<?= e($twitterCard) ?>">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($description) ?>">
    <meta name="twitter:image" content="<?= e($shareImage) ?>">
    <meta name="twitter:image:src" content="<?= e($shareImage) ?>">
    <meta name="twitter:image:alt" content="<?= e($shareImageAlt) ?>">
    <meta name="theme-color" content="#f6f9fc">
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>?v=20260806logo2">
    <?php foreach ($structuredData as $schema): ?>
        <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <?php endforeach; ?>
    <?php if (! empty($showAds)): ?>
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= e((string) env('ADSENSE_PUBLISHER_ID')) ?>" crossorigin="anonymous"></script>
    <?php endif; ?>
</head>
<body class="public-shell">
    <header class="site-header">
        <div class="site-header-inner">
            <a class="brand header-brand-logo" href="<?= url('/') ?>" aria-label="<?= e($siteName) ?> home">
                <img
                    src="<?= asset('assets/img/my-prompt-art-logo.webp') ?>"
                    alt="MyPromptArt — Creative AI Prompts &amp; Ideas"
                    width="1200"
                    height="408"
                >
            </a>
            <form class="header-search" action="<?= url('/prompts') ?>" method="get" role="search">
                <label class="sr-only" for="header-q">Search prompts</label>
                <input id="header-q" name="q" type="search" placeholder="Search prompts">
                <button class="button button-small" type="submit">Search</button>
            </form>
            <nav class="site-nav" aria-label="Main navigation">
                <a href="<?= url('/prompts') ?>">Library</a>
                <a href="<?= url('/about') ?>">About</a>
                <a href="<?= url('/contact') ?>">Contact</a>
            </nav>
        </div>
    </header>

    <?php require base_path('resources/views/partials/flash.php'); ?>

    <main>
        <?= $content ?>
    </main>

    <?php if (! empty($showAds)): ?>
        <?php require base_path('resources/views/partials/ad-slot.php'); ?>
    <?php endif; ?>

    <footer class="site-footer">
        <div>
            <strong>Prompt Library</strong>
            <p>Completed prompts. Search, open, copy.</p>
        </div>
        <nav aria-label="Footer navigation">
            <a href="<?= url('/privacy-policy') ?>">Privacy</a>
            <a href="<?= url('/terms') ?>">Terms</a>
            <a href="<?= url('/robots.txt') ?>">Robots</a>
        </nav>
    </footer>
    <script src="<?= asset('assets/js/app.js') ?>" defer></script>
</body>
</html>
