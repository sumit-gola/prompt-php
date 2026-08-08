<?php
$pageTitle = $title ?? 'Account';
$siteName = \App\Services\SeoService::siteName();
?>
<!doctype html>
<html lang="en">
<head>
    <?php require base_path('resources/views/partials/google-tag-manager-head.php'); ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>document.documentElement.classList.add('js');</script>
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($pageTitle) ?> | <?= e($siteName) ?></title>
    <link rel="icon" href="<?= asset('favicon.png') ?>" type="image/png" sizes="512x512">
    <link rel="apple-touch-icon" href="<?= asset('apple-touch-icon.png') ?>" sizes="180x180">
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>?v=20260808neon-field1">
</head>
<body class="auth-shell auth-page">
    <?php require base_path('resources/views/partials/google-tag-manager-body.php'); ?>
    <?php require base_path('resources/views/partials/neon-field.php'); ?>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <?php require base_path('resources/views/partials/site-header.php'); ?>
    <main class="auth-main" id="main-content">
        <section class="auth-panel">
            <?php require base_path('resources/views/partials/flash.php'); ?>
            <?= $content ?>
        </section>
    </main>
    <script src="<?= asset('assets/js/app.js') ?>?v=20260807category1" defer></script>
</body>
</html>
