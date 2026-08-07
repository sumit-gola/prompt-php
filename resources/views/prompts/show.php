<?php $detailImage = $promptImage ?? \App\Services\PromptImageService::metadata($prompt); ?>
<article class="prompt-detail">
    <aside class="detail-media">
        <figure class="prompt-image-figure">
            <div class="prompt-placeholder">
                <span><?= e(strtoupper(substr((string) $prompt['category'], 0, 2))) ?></span>
                <?php if ($detailImage !== null): ?>
                    <a
                        class="prompt-full-image-link"
                        href="<?= e($detailImage['full_src']) ?>"
                        aria-label="Open full-size image: <?= e($detailImage['alt']) ?>"
                        target="_blank"
                        rel="noopener"
                    >
                        <?php
                        $image = $detailImage;
                        $imageLoading = 'eager';
                        $imageFetchPriority = 'high';
                        $imageSizes = '(max-width: 900px) 100vw, 320px';
                        ?>
                        <?php require base_path('resources/views/partials/responsive-image.php'); ?>
                    </a>
                <?php endif; ?>
            </div>
            <?php if ($detailImage !== null): ?>
                <figcaption><?= e($detailImage['caption']) ?> Select the image to open the full-size preview.</figcaption>
            <?php endif; ?>
        </figure>
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
        <section class="prompt-trust-ledger" aria-labelledby="editorial-record-heading">
            <h2 id="editorial-record-heading">Editorial record</h2>
            <dl>
                <div>
                    <dt>Curated by</dt>
                    <dd>MyPromptArt</dd>
                </div>
                <div>
                    <dt>Tested with</dt>
                    <dd><?= $testedModels !== '' ? e($testedModels) : 'Not yet recorded' ?></dd>
                </div>
                <div>
                    <dt>Published</dt>
                    <dd>
                        <?php if ($publishedAt !== null): ?>
                            <time datetime="<?= e($publishedAt) ?>"><?= e(date('j F Y', strtotime($publishedAt))) ?></time>
                        <?php else: ?>
                            Not recorded
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt>Last reviewed</dt>
                    <dd>
                        <?php if ($reviewedAt !== null): ?>
                            <time datetime="<?= e($reviewedAt) ?>"><?= e(date('j F Y', strtotime($reviewedAt))) ?></time>
                        <?php else: ?>
                            Not yet recorded
                        <?php endif; ?>
                    </dd>
                </div>
                <?php if ($sourceUrl !== null): ?>
                    <div>
                        <dt>Source record</dt>
                        <dd><a href="<?= e($sourceUrl) ?>"><?= e($sourceLabel) ?></a></dd>
                    </div>
                <?php endif; ?>
            </dl>
        </section>
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
        <div class="prompt-grid compact-grid prompt-masonry">
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
