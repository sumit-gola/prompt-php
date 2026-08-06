<?php $pageTitle = $title ?? 'Account'; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($pageTitle) ?> - Prompt Library</title>
    <link rel="icon" href="<?= asset('favicon.png') ?>" type="image/png" sizes="512x512">
    <link rel="apple-touch-icon" href="<?= asset('apple-touch-icon.png') ?>" sizes="180x180">
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>?v=20260806logo2">
</head>
<body class="auth-shell">
    <main class="auth-panel">
        <a class="brand auth-brand" href="<?= url('/') ?>">
            <span class="brand-mark">PL</span>
            <span>Prompt Library</span>
        </a>
        <?php require base_path('resources/views/partials/flash.php'); ?>
        <?= $content ?>
    </main>
</body>
</html>
