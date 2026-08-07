<?php
$currentPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$currentPath = '/' . trim($currentPath, '/');
$currentPath = $currentPath === '/' ? '/' : rtrim($currentPath, '/');
$isHome = $currentPath === '/';
$isLibrary = $currentPath === '/prompts'
    || str_starts_with($currentPath, '/prompts/')
    || str_starts_with($currentPath, '/ai-prompts/');
$isAbout = $currentPath === '/about';
$isContact = $currentPath === '/contact';
$homeExploreUrl = $isHome ? '#explore' : url('/#explore');
$homeCategoriesUrl = $isHome ? '#categories' : url('/#categories');
?>
<header class="site-header site-header--public" data-site-header>
    <div class="site-header-inner">
        <a class="brand home-brand" href="<?= url('/') ?>" aria-label="<?= e($siteName) ?> home">
            <img
                class="home-brand-logo"
                src="<?= asset('assets/img/my-prompt-art-logo.webp') ?>?v=20260807color1"
                alt="MyPromptArt — Creative AI Prompts &amp; Ideas"
                width="1200"
                height="408"
            >
            <span class="sr-only"><?= e(\App\Services\SeoService::siteAlternateName()) ?></span>
        </a>

        <button
            class="site-nav-toggle"
            type="button"
            aria-expanded="false"
            aria-controls="site-navigation"
            data-site-nav-toggle
        >
            <span class="site-nav-toggle-icon" aria-hidden="true"><i></i><i></i><i></i></span>
            <span class="site-nav-toggle-label">Menu</span>
        </button>

        <div class="site-header-menu" id="site-navigation" data-site-nav-menu>
            <nav class="site-nav home-site-nav" aria-label="Main navigation">
                <a
                    class="<?= $isHome ? 'is-active' : '' ?>"
                    href="<?= e($homeExploreUrl) ?>"
                    data-home-nav="explore"
                    <?= $isHome ? 'aria-current="location"' : '' ?>
                >Explore</a>
                <a href="<?= e($homeCategoriesUrl) ?>" data-home-nav="categories">Categories</a>
                <a
                    class="<?= $isAbout ? 'is-active' : '' ?>"
                    href="<?= url('/about') ?>"
                    <?= $isAbout ? 'aria-current="page"' : '' ?>
                >About</a>
                <a
                    class="<?= $isContact ? 'is-active' : '' ?>"
                    href="<?= url('/contact') ?>"
                    <?= $isContact ? 'aria-current="page"' : '' ?>
                >Contact</a>
            </nav>

            <div class="home-header-actions">
                <a class="home-search-shortcut" href="<?= url('/prompts') ?>" aria-label="Search prompt library">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="5.5"></circle><path d="m15 15 4 4"></path></svg>
                </a>
                <a
                    class="button button-small<?= $isLibrary ? ' is-active' : '' ?>"
                    href="<?= url('/prompts') ?>"
                    <?= $isLibrary ? 'aria-current="page"' : '' ?>
                >Browse prompts</a>
            </div>
        </div>
    </div>
</header>
