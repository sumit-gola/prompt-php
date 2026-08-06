<section class="library-header compact">
    <div class="library-title">
        <p class="eyebrow">Prompt library</p>
        <h1>Completed prompts</h1>
        <p class="muted"><?= (int) $results['total'] ?> result<?= (int) $results['total'] === 1 ? '' : 's' ?></p>
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

        <button class="button" type="submit">Apply</button>
    </form>
</section>

<section class="content-band">
    <?php if ($results['items'] === []): ?>
        <div class="empty-state">No completed prompts match this search.</div>
    <?php else: ?>
        <div class="prompt-grid">
            <?php foreach ($results['items'] as $prompt): ?>
                <?php require base_path('resources/views/partials/prompt-card.php'); ?>
            <?php endforeach; ?>
        </div>
        <?php require base_path('resources/views/partials/pagination.php'); ?>
    <?php endif; ?>
</section>
