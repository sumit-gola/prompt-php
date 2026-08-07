<section class="library-header compact">
    <div class="library-title">
        <?php if ($breadcrumbs !== []): ?>
            <nav class="breadcrumbs" aria-label="Breadcrumb">
                <ol>
                    <?php foreach ($breadcrumbs as $index => $item): ?>
                        <li>
                            <?php if ($index < count($breadcrumbs) - 1): ?>
                                <a href="<?= e($item['url']) ?>"><?= e($item['name']) ?></a>
                            <?php else: ?>
                                <span aria-current="page"><?= e($item['name']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        <?php endif; ?>
        <p class="eyebrow"><?= e($listingEyebrow) ?></p>
        <h1><?= e($listingHeading) ?></h1>
        <p class="listing-intro"><?= e($listingIntro) ?></p>
        <p class="muted"><?= (int) $results['total'] ?> result<?= (int) $results['total'] === 1 ? '' : 's' ?></p>
        <div class="category-strip" aria-label="Prompt categories">
            <?php foreach ($categories as $category): ?>
                <a href="<?= url('/ai-prompts/' . rawurlencode($category)) ?>">
                    <?= e(\App\Services\SeoService::categoryName($category)) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <form class="filter-panel" action="<?= url('/prompts') ?>" method="get">
        <label for="q">Search
            <input id="q" name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Prompt text, title, category">
        </label>

        <label for="category">Category
            <select id="category" name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category) ?>"<?= selected($filters['category'], $category) ?>><?= e(ucfirst($category)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label for="sort">Sort
            <select id="sort" name="sort">
                <option value="newest"<?= selected($filters['sort'], 'newest') ?>>Newest</option>
                <option value="oldest"<?= selected($filters['sort'], 'oldest') ?>>Oldest</option>
                <option value="popular"<?= selected($filters['sort'], 'popular') ?>>Popular</option>
                <option value="most_copied"<?= selected($filters['sort'], 'most_copied') ?>>Most copied</option>
                <option value="category"<?= selected($filters['sort'], 'category') ?>>Category</option>
            </select>
        </label>

        <button class="button" type="submit">Apply filters</button>
    </form>
</section>

<section class="content-band">
    <?php if ($results['items'] === []): ?>
        <div class="empty-state">
            <strong>No completed prompts match this search.</strong>
            <p>Try a shorter phrase, another category, or reset the filters.</p>
            <a class="button button-small button-ghost" href="<?= url('/prompts') ?>">Reset filters</a>
        </div>
    <?php else: ?>
        <div class="prompt-grid prompt-masonry">
            <?php foreach ($results['items'] as $index => $prompt): ?>
                <?php
                $cardImageLoading = $index === 0 ? 'eager' : 'lazy';
                $cardImageFetchPriority = $index === 0 ? 'high' : 'auto';
                ?>
                <?php require base_path('resources/views/partials/prompt-card.php'); ?>
            <?php endforeach; ?>
        </div>
        <?php require base_path('resources/views/partials/pagination.php'); ?>
    <?php endif; ?>
</section>
