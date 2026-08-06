<?php
$pageTitle = $title ?? 'Admin';
$user = \App\Core\Auth::user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($pageTitle) ?> - Prompt Library Admin</title>
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>?v=20260806signal">
</head>
<body class="admin-shell">
    <aside class="admin-sidebar">
        <a class="brand admin-brand" href="<?= url('/admin') ?>">
            <span class="brand-mark">PL</span>
            <span>Admin</span>
        </a>
        <nav class="admin-nav" aria-label="Admin navigation">
            <a href="<?= url('/admin') ?>">Dashboard</a>
            <a href="<?= url('/admin/prompts') ?>">Prompts</a>
            <a href="<?= url('/admin/prompts/create') ?>">Create</a>
            <a href="<?= url('/prompts') ?>">Public library</a>
        </nav>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar">
            <div>
                <p class="eyebrow">Signed in as</p>
                <strong><?= e($user['email'] ?? '') ?></strong>
            </div>
            <form method="post" action="<?= url('/logout') ?>">
                <?= csrf_field() ?>
                <button class="button button-ghost" type="submit">Sign out</button>
            </form>
        </header>
        <?php require base_path('resources/views/partials/flash.php'); ?>
        <main class="admin-content">
            <?= $content ?>
        </main>
    </div>
    <script src="<?= asset('assets/js/app.js') ?>" defer></script>
</body>
</html>
