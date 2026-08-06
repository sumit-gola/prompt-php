<section class="library-header">
    <div class="library-title">
        <p class="eyebrow">Prompt index</p>
        <h1>Search <?= (int) $publicCompletedCount ?> AI prompts.</h1>
        <div class="hero-stats" aria-label="Library summary">
            <span><strong><?= (int) $publicCompletedCount ?></strong> completed</span>
            <span><strong><?= count($categories) ?></strong> categories</span>
            <span><strong><?= (int) ($stats['copies'] ?? 0) ?></strong> copies</span>
        </div>
        <div class="category-strip" aria-label="Prompt categories">
            <?php foreach ($categories as $category): ?>
                <a href="<?= url('/prompts/category/' . rawurlencode($category)) ?>">
                    <?= e(\App\Services\SeoService::categoryName($category)) ?>
                    <span class="sr-only">(<?= (int) ($categoryCounts[$category] ?? 0) ?> prompts)</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <form class="search-panel" action="<?= url('/prompts') ?>" method="get">
        <label for="home-q">Search prompts</label>
        <div class="search-row">
            <input id="home-q" name="q" type="search" placeholder="Try product shot, portrait lighting, fashion">
            <button class="button" type="submit">Search</button>
        </div>
    </form>
</section>

<section class="content-band latest-band">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Latest published</p>
            <h2>Newest in the library</h2>
        </div>
        <a class="button button-ghost" href="<?= url('/prompts') ?>">View all</a>
    </div>

    <?php if ($prompts === []): ?>
        <div class="empty-state">No completed prompts are published yet.</div>
    <?php else: ?>
        <div class="prompt-grid">
            <?php foreach ($prompts as $index => $prompt): ?>
                <?php
                $cardImageLoading = $index === 0 ? 'eager' : 'lazy';
                $cardImageFetchPriority = $index === 0 ? 'high' : 'auto';
                ?>
                <?php require base_path('resources/views/partials/prompt-card.php'); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
