<?php
$heroPrompts = [];

foreach ($prompts as $prompt) {
    $heroImage = \App\Services\PromptImageService::metadata($prompt);

    if ($heroImage !== null) {
        $heroPrompts[] = [
            'prompt' => $prompt,
            'image' => $heroImage,
        ];
    }

    if (count($heroPrompts) === 3) {
        break;
    }
}

$popularSearches = ['Portrait', 'Cinematic', 'Fashion', 'Product', 'Anime'];
$displayPromptCount = $publicCompletedCount >= 1000
    ? number_format((int) floor($publicCompletedCount / 1000) * 1000) . '+'
    : number_format($publicCompletedCount);
?>

<svg class="home-icon-sprite" aria-hidden="true">
    <symbol id="home-icon-portrait" viewBox="0 0 24 24">
        <circle cx="12" cy="8" r="3.25"></circle>
        <path d="M5.5 20c.5-4.1 2.7-6.2 6.5-6.2s6 2.1 6.5 6.2"></path>
        <path d="M4 4.5v-1h3M20 4.5v-1h-3M4 18.5v2h3M20 18.5v2h-3"></path>
    </symbol>
    <symbol id="home-icon-product" viewBox="0 0 24 24">
        <path d="m5 8 7-4 7 4-7 4-7-4Z"></path>
        <path d="M5 8v8l7 4 7-4V8M12 12v8"></path>
    </symbol>
    <symbol id="home-icon-fashion" viewBox="0 0 24 24">
        <path d="M9.5 6.5A2.5 2.5 0 1 1 12 9v1"></path>
        <path d="m4 17 8-7 8 7c.8.7.3 2-1 2H5c-1.3 0-1.8-1.3-1-2Z"></path>
    </symbol>
    <symbol id="home-icon-lifestyle" viewBox="0 0 24 24">
        <circle cx="17" cy="7" r="2.5"></circle>
        <path d="m3.5 18 5-6 3.5 4 2-2.5 6.5 6.5h-17Z"></path>
        <path d="M3.5 4.5h7"></path>
    </symbol>
    <symbol id="home-icon-art" viewBox="0 0 24 24">
        <path d="M12 3a9 9 0 0 0 0 18h1.2a1.8 1.8 0 0 0 1.3-3c-.8-.9-.2-2.4 1-2.4H18A3 3 0 0 0 21 12c0-5-4-9-9-9Z"></path>
        <circle cx="7.5" cy="10" r=".8"></circle>
        <circle cx="10" cy="6.8" r=".8"></circle>
        <circle cx="14" cy="6.8" r=".8"></circle>
        <circle cx="17" cy="9.5" r=".8"></circle>
    </symbol>
    <symbol id="home-icon-sparkles" viewBox="0 0 24 24">
        <path d="m12 3 1.1 3.4L16.5 8l-3.4 1.2L12 12.5l-1.2-3.3L7.5 8l3.3-1.6L12 3Z"></path>
        <path d="m18.5 13 .7 2.2 2.3.8-2.3.8-.7 2.2-.8-2.2-2.2-.8 2.2-.8.8-2.2ZM5.5 12l.6 1.7 1.7.6-1.7.6-.6 1.6-.6-1.6-1.7-.6 1.7-.6.6-1.7Z"></path>
    </symbol>
</svg>

<div class="home-tech-background" aria-hidden="true">
    <span class="home-tech-grid home-tech-grid--hero"></span>
    <span class="home-tech-grid home-tech-grid--gallery"></span>
    <span class="home-tech-orbit home-tech-orbit--one"></span>
    <span class="home-tech-orbit home-tech-orbit--two"></span>
    <span class="home-tech-circuit home-tech-circuit--one"><i></i><i></i><i></i></span>
    <span class="home-tech-circuit home-tech-circuit--two"><i></i><i></i><i></i></span>
</div>

<section class="home-hero" id="explore" data-home-section="explore" aria-labelledby="home-heading">
    <div class="home-hero-copy">
        <p class="home-kicker"><span></span> Curated prompts, ready to copy</p>
        <h1 id="home-heading">Find the perfect prompt for any <em>AI</em> image idea.</h1>
        <p class="home-hero-intro">Browse curated, copy-ready AI prompts for portraits, product photography, fashion, lifestyle and digital art.</p>

        <form class="home-search" action="<?= url('/prompts') ?>" method="get" role="search">
            <label class="sr-only" for="home-q">Search the prompt library</label>
            <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"></circle><path d="m16 16 4 4"></path></svg>
            <input id="home-q" name="q" type="search" placeholder="Search anything... (e.g. cinematic portrait)">
            <button type="submit" aria-label="Search prompts">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="5.5"></circle><path d="m15 15 4 4"></path></svg>
            </button>
        </form>

        <div class="popular-searches" aria-label="Popular searches">
            <span>Popular:</span>
            <?php foreach ($popularSearches as $search): ?>
                <a href="<?= url('/prompts?q=' . rawurlencode($search)) ?>"><?= e($search) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="home-hero-gallery" aria-label="Featured prompt artwork">
        <?php foreach ($heroPrompts as $index => $item): ?>
            <?php
            $prompt = $item['prompt'];
            $image = $item['image'];
            $identifier = \App\Models\Prompt::publicIdentifier($prompt);
            $imageLoading = 'eager';
            $imageFetchPriority = $index === 0 ? 'high' : 'auto';
            $imageSizes = $index === 0
                ? '(max-width: 760px) 72vw, 360px'
                : '(max-width: 760px) 32vw, 170px';
            ?>
            <a class="hero-art hero-art-<?= $index + 1 ?>" href="<?= url('/prompts/' . $identifier) ?>" aria-label="Open <?= e((string) $prompt['title']) ?>">
                <?php require base_path('resources/views/partials/responsive-image.php'); ?>
            </a>
        <?php endforeach; ?>

        <?php for ($index = count($heroPrompts); $index < 3; $index++): ?>
            <a class="hero-art hero-art-<?= $index + 1 ?> hero-art-placeholder" href="<?= url('/prompts') ?>">
                <svg aria-hidden="true" viewBox="0 0 24 24"><use href="#home-icon-sparkles"></use></svg>
                <span>Explore art</span>
            </a>
        <?php endfor; ?>

        <a class="hero-count" href="<?= url('/prompts') ?>">
            <strong><?= e($displayPromptCount) ?></strong>
            <span>Prompts</span>
        </a>
    </div>
</section>

<section class="home-categories" id="categories" data-home-section="categories" aria-label="Browse by categories">
    <?php
    $activeCategory = '';
    $categorySliderVariant = 'home';
    $includeAllCategory = false;
    require base_path('resources/views/partials/category-slider.php');
    ?>
</section>

<section class="content-band latest-band home-latest" aria-labelledby="latest-heading">
    <div class="home-section-heading">
        <div>
            <p class="home-section-kicker">Fresh inspiration</p>
            <h2 id="latest-heading">Latest prompts</h2>
        </div>
        <a class="home-sort-link" href="<?= url('/prompts?sort=newest') ?>">Newest first <span aria-hidden="true">&#8964;</span></a>
    </div>

    <?php if ($prompts === []): ?>
        <div class="empty-state">No completed prompts are published yet.</div>
    <?php else: ?>
        <div class="prompt-grid prompt-masonry">
            <?php foreach ($prompts as $index => $prompt): ?>
                <?php
                $cardImageLoading = $index === 0 ? 'eager' : 'lazy';
                $cardImageFetchPriority = $index === 0 ? 'high' : 'auto';
                ?>
                <?php require base_path('resources/views/partials/prompt-card.php'); ?>
            <?php endforeach; ?>
        </div>
        <div class="home-library-cta">
            <a class="button" href="<?= url('/prompts') ?>">Explore the full library <span aria-hidden="true">&#8594;</span></a>
        </div>
    <?php endif; ?>
</section>
