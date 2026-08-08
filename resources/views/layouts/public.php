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
$socialIcons = [
    'Facebook' => '<path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.099 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.413c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236V7.92h-1.513c-1.49 0-1.956.931-1.956 1.887v2.266h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.099 24 12.073Z"/>',
    'X' => '<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231Zm-1.161 17.52h1.833L7.084 4.126H5.117Z"/>',
    'Instagram' => '<rect x="2.75" y="2.75" width="18.5" height="18.5" rx="5.25" fill="none" stroke="currentColor" stroke-width="2.25"/><circle cx="12" cy="12" r="4.25" fill="none" stroke="currentColor" stroke-width="2.25"/><circle cx="17.45" cy="6.55" r="1.25"/>',
    'YouTube' => '<path d="M23.5 6.2a3.05 3.05 0 0 0-2.15-2.16C19.46 3.53 12 3.53 12 3.53s-7.46 0-9.35.51A3.05 3.05 0 0 0 .5 6.2C0 8.09 0 12 0 12s0 3.91.5 5.8a3.05 3.05 0 0 0 2.15 2.16c1.89.51 9.35.51 9.35.51s7.46 0 9.35-.51a3.05 3.05 0 0 0 2.15-2.16c.5-1.89.5-5.8.5-5.8s0-3.91-.5-5.8ZM9.55 15.57V8.43L15.82 12Z"/>',
    'Pinterest' => '<path d="M12 0C5.37 0 0 5.37 0 12c0 4.91 2.95 9.13 7.18 10.99-.1-.84-.19-2.14.04-3.06l1.41-5.98s-.36-.72-.36-1.79c0-1.68.97-2.93 2.18-2.93 1.03 0 1.53.77 1.53 1.7 0 1.03-.66 2.58-1 4.01-.28 1.2.6 2.18 1.79 2.18 2.15 0 3.8-2.27 3.8-5.54 0-2.9-2.08-4.92-5.05-4.92-3.44 0-5.46 2.58-5.46 5.25 0 1.04.4 2.15.9 2.76.1.12.11.22.08.34l-.34 1.38c-.05.22-.18.27-.41.16-1.51-.7-2.46-2.92-2.46-4.7 0-3.83 2.78-7.34 8.02-7.34 4.21 0 7.48 3 7.48 7.01 0 4.18-2.64 7.55-6.29 7.55-1.23 0-2.38-.64-2.78-1.39l-.76 2.88c-.27 1.06-1.01 2.38-1.51 3.19 1.14.35 2.34.54 3.58.54C18.63 24 24 18.63 24 12S18.63 0 12 0Z"/>',
];
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
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>?v=20260808neon-circuit1-home-tech2-gallery-orbit1">
    <?php foreach ($structuredData as $schema): ?>
        <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <?php endforeach; ?>
    <?php if ($adsense['loader_enabled']): ?>
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= e($adsense['client_id']) ?>" crossorigin="anonymous"></script>
    <?php endif; ?>
</head>
<body class="public-shell<?= ! empty($bodyClass) ? ' ' . e($bodyClass) : '' ?>">
    <?php require base_path('resources/views/partials/google-tag-manager-body.php'); ?>
    <?php require base_path('resources/views/partials/neon-field.php'); ?>
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
                            <a
                                class="footer-social-link footer-social-link--<?= e(strtolower($platform)) ?>"
                                href="<?= e($profileUrl) ?>"
                                target="_blank"
                                rel="me noopener noreferrer"
                                aria-label="Visit MyPromptArt on <?= e($platform) ?> (opens in a new tab)"
                                title="<?= e($platform) ?>"
                            >
                                <svg class="footer-social-icon" aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                                    <?= $socialIcons[$platform] ?? '' ?>
                                </svg>
                            </a>
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
    <script src="<?= asset('assets/js/app.js') ?>?v=20260807homecategory1" defer></script>
</body>
</html>
