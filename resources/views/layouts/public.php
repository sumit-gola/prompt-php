<?php
$siteName = \App\Services\SeoService::siteName();
$pageTitle = $metaTitle ?? $title ?? $siteName;
$description = $metaDescription ?? 'Browse and copy curated AI image prompts.';
$canonicalUrl = $canonical ?? app_url($_SERVER['REQUEST_URI'] ?? '/');
$robotsDirective = ! empty($noindex)
    ? (! empty($nofollow) ? 'noindex,nofollow' : 'noindex,follow')
    : 'index,follow';
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
$googleVerification = trim((string) env('GOOGLE_SITE_VERIFICATION', ''));
$bingVerification = trim((string) env('BING_SITE_VERIFICATION', ''));
$adsense = \App\Services\AdSenseService::configuration(! empty($showAds), $adPlacement ?? null);
?>
<!doctype html>
<html lang="en">
<head>
    <?php require base_path('resources/views/partials/google-tag-manager-head.php'); ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>document.documentElement.classList.add('js');</script>
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta name="robots" content="<?= e($robotsDirective) ?>">
    <meta name="googlebot" content="<?= e($robotsDirective) ?>,max-snippet:-1,max-image-preview:large,max-video-preview:-1">
    <?php if ($googleVerification !== ''): ?>
        <meta name="google-site-verification" content="<?= e($googleVerification) ?>">
    <?php endif; ?>
    <?php if ($bingVerification !== ''): ?>
        <meta name="msvalidate.01" content="<?= e($bingVerification) ?>">
    <?php endif; ?>
    <?php if ($adsense['client_id'] !== null): ?>
        <meta name="google-adsense-account" content="<?= e($adsense['client_id']) ?>">
    <?php endif; ?>
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
    <?php if (! empty($prevUrl)): ?>
        <link rel="prev" href="<?= e($prevUrl) ?>">
    <?php endif; ?>
    <?php if (! empty($nextUrl)): ?>
        <link rel="next" href="<?= e($nextUrl) ?>">
    <?php endif; ?>
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
    <?php if ($ogType === 'article' && ! empty($ogPublishedTime)): ?>
        <meta property="article:published_time" content="<?= e($ogPublishedTime) ?>">
    <?php endif; ?>
    <?php if ($ogType === 'article' && ! empty($ogUpdatedTime)): ?>
        <meta property="article:modified_time" content="<?= e($ogUpdatedTime) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="<?= e($twitterCard) ?>">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($description) ?>">
    <meta name="twitter:image" content="<?= e($shareImage) ?>">
    <meta name="twitter:image:src" content="<?= e($shareImage) ?>">
    <meta name="twitter:image:alt" content="<?= e($shareImageAlt) ?>">
    <meta name="theme-color" content="<?= ($bodyClass ?? '') === 'home-page' ? '#ffffff' : '#f6f9fc' ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>?v=20260807footer1-category1">
    <?php foreach ($structuredData as $schema): ?>
        <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <?php endforeach; ?>
    <?php if ($adsense['loader_enabled']): ?>
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= e($adsense['client_id']) ?>" crossorigin="anonymous"></script>
    <?php endif; ?>
</head>
<body class="public-shell<?= ! empty($bodyClass) ? ' ' . e($bodyClass) : '' ?>">
    <?php require base_path('resources/views/partials/google-tag-manager-body.php'); ?>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <?php require base_path('resources/views/partials/site-header.php'); ?>

    <?php require base_path('resources/views/partials/flash.php'); ?>

    <main id="main-content">
        <?= $content ?>
    </main>

    <?php if ($adsense['slot_id'] !== null): ?>
        <?php
        $adClientId = $adsense['client_id'];
        $adSlotId = $adsense['slot_id'];
        $adPlacementName = $adsense['placement'];
        ?>
        <?php require base_path('resources/views/partials/ad-slot.php'); ?>
    <?php endif; ?>

    <footer class="site-footer">
        <div class="footer-frame">
            <div class="footer-atmosphere" aria-hidden="true">
                <span></span>
                <span></span>
            </div>

            <section class="footer-callout" aria-labelledby="footer-heading">
                <div class="footer-callout-copy">
                    <p class="footer-kicker"><span aria-hidden="true"></span> Curated for creators</p>
                    <h2 id="footer-heading">Your next image starts with <em>the right words.</em></h2>
                    <p>Find a finished, thoughtfully curated prompt and make the idea your own.</p>
                </div>
                <a class="footer-cta" href="<?= url('/prompts') ?>">
                    <span>Explore prompts</span>
                    <i aria-hidden="true">&#8599;</i>
                </a>
            </section>

            <div class="footer-prompt-formula" role="group" aria-label="The building blocks of an image prompt">
                <span class="footer-formula-label">Build the scene</span>
                <div>
                    <span>Subject</span>
                    <b aria-hidden="true">+</b>
                    <span>Light</span>
                    <b aria-hidden="true">+</b>
                    <span>Lens</span>
                    <b aria-hidden="true">+</b>
                    <span>Mood</span>
                </div>
            </div>

            <div class="footer-directory">
                <div class="footer-brand-block">
                    <a class="footer-brand" href="<?= url('/') ?>" aria-label="MyPromptArt home">
                        <span class="footer-brand-mark" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
                        <strong>MyPromptArt</strong>
                    </a>
                    <p>Completed prompts. Search, open, copy.</p>
                </div>

                <div class="footer-link-group">
                    <p class="footer-label">Discover</p>
                    <nav aria-label="Discover MyPromptArt">
                        <a href="<?= url('/prompts') ?>">Prompt library</a>
                        <a href="<?= url('/about') ?>">About</a>
                        <a href="<?= url('/contact') ?>">Contact</a>
                    </nav>
                </div>

                <div class="footer-link-group footer-community">
                    <p class="footer-label">Follow the work</p>
                    <nav class="footer-social" aria-label="Social media">
                        <?php foreach (\App\Services\SeoService::socialProfiles() as $platform => $profileUrl): ?>
                            <a href="<?= e($profileUrl) ?>" target="_blank" rel="me noopener noreferrer"><?= e($platform) ?></a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> MyPromptArt</p>
                <nav aria-label="Legal and technical links">
                    <a href="<?= url('/privacy-policy') ?>">Privacy</a>
                    <a href="<?= url('/terms') ?>">Terms</a>
                    <a href="<?= url('/sitemap.xml') ?>">Sitemap</a>
                    <a href="<?= url('/robots.txt') ?>">Robots</a>
                </nav>
                <p class="footer-status"><span aria-hidden="true"></span> Ready when inspiration is.</p>
            </div>
        </div>
    </footer>
    <script src="<?= asset('assets/js/app.js') ?>?v=20260807category1" defer></script>
</body>
</html>
