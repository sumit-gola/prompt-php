<?php
$identifier = \App\Models\Prompt::publicIdentifier($prompt);
$fullTitle = (string) $prompt['title'];
$cardTitle = str_limit_words($fullTitle, 44);
$cardImageLoading = $cardImageLoading ?? 'lazy';
$cardImageFetchPriority = $cardImageFetchPriority ?? 'auto';
?>
<article class="prompt-card">
    <a class="prompt-thumb" href="<?= url('/prompts/' . $identifier) ?>">
        <span><?= e(strtoupper(substr((string) $prompt['category'], 0, 2))) ?></span>
        <?php if (! empty($prompt['thumbnail_path'])): ?>
            <img
                src="<?= e(asset((string) $prompt['thumbnail_path'])) ?>"
                alt="<?= e($fullTitle . ' AI image prompt preview') ?>"
                width="640"
                height="420"
                loading="<?= e($cardImageLoading) ?>"
                fetchpriority="<?= e($cardImageFetchPriority) ?>"
                decoding="async"
                onerror="this.remove()"
            >
        <?php endif; ?>
    </a>
    <div class="prompt-card-body">
        <div class="prompt-card-top">
            <div class="prompt-meta">
                <a href="<?= url('/prompts/category/' . rawurlencode((string) $prompt['category'])) ?>">
                    <?= e(\App\Services\SeoService::categoryName((string) $prompt['category'])) ?>
                </a>
                <span><?= (int) $prompt['copy_count'] ?> copied</span>
            </div>
            <span class="prompt-id">#<?= (int) $prompt['id'] ?></span>
        </div>
        <h2><a href="<?= url('/prompts/' . $identifier) ?>" title="<?= e($fullTitle) ?>"><?= e($cardTitle) ?></a></h2>
        <p><?= e(str_limit((string) $prompt['prompt'], 150)) ?></p>
        <div class="card-actions">
            <a class="button button-small button-ghost" href="<?= url('/prompts/' . $identifier) ?>">Open</a>
            <button class="button button-small copy-button" data-copy-url="<?= url('/prompts/' . $prompt['id'] . '/copy') ?>" data-csrf="<?= e(csrf_token()) ?>" type="button">Copy</button>
        </div>
    </div>
</article>
