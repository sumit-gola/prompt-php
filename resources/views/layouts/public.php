<?php
$pageTitle = $metaTitle ?? $title ?? 'Prompt Library';
$description = $metaDescription ?? 'Browse and copy curated AI image prompts.';
$canonicalUrl = $canonical ?? app_url($_SERVER['REQUEST_URI'] ?? '/');
$robots = ! empty($noindex) ? 'noindex,follow' : 'index,follow';
$user = \App\Core\Auth::user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta name="robots" content="<?= e($robots) ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <?php if (! empty($ogImage)): ?>
        <meta property="og:image" content="<?= e($ogImage) ?>">
    <?php endif; ?>
    <meta name="theme-color" content="#111615">
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>?v=20260806n">
    <?php if (! empty($showAds)): ?>
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= e((string) env('ADSENSE_PUBLISHER_ID')) ?>" crossorigin="anonymous"></script>
    <?php endif; ?>
</head>
<body class="public-shell">
    <header class="site-header">
        <div class="site-header-inner">
            <a class="brand" href="<?= url('/') ?>">
                <span class="brand-mark">PL</span>
                <span>Prompt Library</span>
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
                <?php if ($user && (int) ($user['is_admin'] ?? 0) === 1): ?>
                    <a href="<?= url('/admin') ?>">Admin</a>
                <?php else: ?>
                    <a href="<?= url('/login') ?>">Sign in</a>
                <?php endif; ?>
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
