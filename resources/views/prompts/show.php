<article class="prompt-detail">
    <aside class="detail-media">
        <div class="prompt-placeholder">
            <span><?= e(strtoupper(substr((string) $prompt['category'], 0, 2))) ?></span>
            <?php if (! empty($prompt['thumbnail_path'])): ?>
                <img
                    src="<?= e(asset((string) $prompt['thumbnail_path'])) ?>"
                    alt="<?= e((string) $prompt['title'] . ' AI image prompt preview') ?>"
                    width="640"
                    height="420"
                    loading="eager"
                    fetchpriority="high"
                    decoding="async"
                    onerror="this.remove()"
                >
            <?php endif; ?>
        </div>
        <div class="detail-counts">
            <span><?= e(ucfirst((string) $prompt['category'])) ?></span>
            <span><?= (int) $prompt['copy_count'] ?> copies</span>
        </div>
    </aside>
    <section class="detail-copy">
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
        <div class="detail-toolbar">
            <a href="<?= url($categoryPath) ?>"><?= e($categoryName) ?></a>
            <span><?= (int) $prompt['copy_count'] ?> copied</span>
        </div>
        <div>
            <p class="eyebrow">Prompt file</p>
            <h1><?= e($prompt['title']) ?></h1>
        </div>
        <?php if ($publishedAt !== null || $modifiedAt !== null): ?>
            <div class="publication-meta">
                <?php if ($publishedAt !== null): ?>
                    <span>Published <time datetime="<?= e($publishedAt) ?>"><?= e(date('M j, Y', strtotime($publishedAt))) ?></time></span>
                <?php endif; ?>
                <?php if ($modifiedAt !== null): ?>
                    <span>Updated <time datetime="<?= e($modifiedAt) ?>"><?= e(date('M j, Y', strtotime($modifiedAt))) ?></time></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="prompt-text"><?= nl2br(e((string) $prompt['prompt'])) ?></div>
        <button class="button copy-button" data-copy-url="<?= url('/prompts/' . $prompt['id'] . '/copy') ?>" data-csrf="<?= e(csrf_token()) ?>" type="button">Copy prompt</button>

        <?php if (! empty($prompt['negative_prompt'])): ?>
            <h2>Negative prompt</h2>
            <div class="prompt-text secondary"><?= nl2br(e((string) $prompt['negative_prompt'])) ?></div>
        <?php endif; ?>

        <?php if ($styleNotes !== []): ?>
            <h2>Style notes</h2>
            <dl class="style-notes">
                <?php foreach ($styleNotes as $key => $value): ?>
                    <?php if (is_scalar($value)): ?>
                        <dt><?= e(str_replace('_', ' ', (string) $key)) ?></dt>
                        <dd><?= e($value) ?></dd>
                    <?php endif; ?>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
    </section>
</article>

<?php if ($related !== []): ?>
    <section class="content-band">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Related</p>
                <h2>More <?= e($prompt['category']) ?> prompts</h2>
            </div>
        </div>
        <div class="prompt-grid compact-grid">
            <?php foreach ($related as $prompt): ?>
                <?php
                $cardImageLoading = 'lazy';
                $cardImageFetchPriority = 'auto';
                ?>
                <?php require base_path('resources/views/partials/prompt-card.php'); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
