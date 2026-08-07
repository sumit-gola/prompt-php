<?php
$identifier = \App\Models\Prompt::publicIdentifier($prompt);
$fullTitle = (string) $prompt['title'];
$cardTitle = str_limit_words($fullTitle, 44);
$cardImage = \App\Services\PromptImageService::metadata($prompt);
$cardImageLoading = 'lazy';
$cardImageFetchPriority = 'auto';
$cardTitleId = 'prompt-card-title-' . (int) $prompt['id'];
?>
<article class="prompt-card prompt-gallery-card">
    <a
        class="prompt-thumb<?= $cardImage === null ? ' is-placeholder' : '' ?>"
        href="<?= url('/prompts/' . $identifier) ?>"
        aria-labelledby="<?= e($cardTitleId) ?>"
    >
        <span><?= e(strtoupper(substr((string) $prompt['category'], 0, 2))) ?></span>
        <?php if ($cardImage !== null): ?>
            <?php
            $image = $cardImage;
            $imageLoading = $cardImageLoading;
            $imageFetchPriority = $cardImageFetchPriority;
            $imageSizes = '(max-width: 620px) 50vw, (max-width: 900px) 33vw, (max-width: 1180px) 25vw, 20vw';
            ?>
            <?php require base_path('resources/views/partials/responsive-image.php'); ?>
        <?php endif; ?>
    </a>

    <button
        class="prompt-card-copy copy-button"
        data-copy-url="<?= url('/prompts/' . $prompt['id'] . '/copy') ?>"
        data-csrf="<?= e(csrf_token()) ?>"
        type="button"
        title="Copy prompt"
    >
        <svg class="prompt-card-copy-icon" aria-hidden="true" viewBox="0 0 24 24">
            <rect x="8" y="8" width="10" height="10" rx="2"></rect>
            <path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"></path>
        </svg>
        <svg class="prompt-card-check-icon" aria-hidden="true" viewBox="0 0 24 24">
            <path d="m6 12 4 4 8-9"></path>
        </svg>
        <span class="sr-only" data-copy-label>Copy prompt: <?= e($fullTitle) ?></span>
    </button>

    <div class="prompt-card-body">
        <div class="prompt-card-top">
            <a class="prompt-card-category" href="<?= url('/ai-prompts/' . rawurlencode((string) $prompt['category'])) ?>">
                <?= e(\App\Services\SeoService::categoryName((string) $prompt['category'])) ?>
            </a>
            <span><?= (int) $prompt['copy_count'] ?> copies</span>
        </div>
        <h2 id="<?= e($cardTitleId) ?>"><a href="<?= url('/prompts/' . $identifier) ?>" title="<?= e($fullTitle) ?>"><?= e($cardTitle) ?></a></h2>
        <span class="prompt-card-open">Open prompt <span aria-hidden="true">&#8599;</span></span>
    </div>
</article>
