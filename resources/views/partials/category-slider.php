<?php
$activeCategory = (string) ($activeCategory ?? '');
$categoryArtwork = is_array($categoryArtwork ?? null) ? $categoryArtwork : [];
$categoryCounts = is_array($categoryCounts ?? null) ? $categoryCounts : [];
$categorySliderVariant = ($categorySliderVariant ?? '') === 'home' ? 'home' : 'library';
$includeAllCategory = isset($includeAllCategory) ? (bool) $includeAllCategory : true;
$categorySliderClass = 'category-slider' . ($categorySliderVariant === 'home' ? ' home-category-slider' : '');
$allCount = array_sum(array_map('intval', $categoryCounts));
$allArtwork = [];

foreach ($categories as $category) {
    if (is_array($categoryArtwork[$category] ?? null)) {
        $allArtwork[] = $categoryArtwork[$category];

        if (count($allArtwork) === 4) {
            break;
        }
    }
}

$sliderItems = [];

if ($includeAllCategory) {
    $sliderItems[] = [
        'slug' => '',
        'label' => 'All prompts',
        'count' => $allCount,
        'artwork' => null,
        'artworks' => $allArtwork,
    ];
}

foreach ($categories as $category) {
    $sliderItems[] = [
        'slug' => $category,
        'label' => \App\Services\SeoService::categoryName($category),
        'count' => (int) ($categoryCounts[$category] ?? 0),
        'artwork' => is_array($categoryArtwork[$category] ?? null) ? $categoryArtwork[$category] : null,
        'artworks' => [],
    ];
}
?>
<nav class="<?= e($categorySliderClass) ?>" data-category-slider aria-label="Browse prompt categories">
    <?php if ($categorySliderVariant === 'home'): ?>
        <span class="home-category-slider-progress" aria-hidden="true">
            <i data-category-slider-progress-thumb></i>
        </span>
    <?php endif; ?>

    <button
        class="category-slider-control category-slider-control--previous"
        type="button"
        data-category-slider-previous
        aria-label="Show previous categories"
    >
        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m14.5 6-6 6 6 6"></path></svg>
    </button>

    <div
        class="category-slider-viewport"
        data-category-slider-viewport
        tabindex="0"
        aria-label="Scrollable category list"
    >
        <div class="category-slider-track">
            <?php foreach ($sliderItems as $item): ?>
                <?php
                $slug = (string) $item['slug'];
                $isActive = $activeCategory === $slug;
                $artwork = $item['artwork'];
                $destination = $slug === ''
                    ? url('/prompts')
                    : url('/ai-prompts/' . rawurlencode($slug));
                ?>
                <a
                    class="category-slider-item<?= $isActive ? ' is-active' : '' ?>"
                    href="<?= e($destination) ?>"
                    data-category-slider-item
                    <?= $isActive ? 'aria-current="page"' : '' ?>
                    aria-label="<?= e((string) $item['label']) ?>, <?= number_format((int) $item['count']) ?> prompts"
                >
                    <span class="category-slider-art" aria-hidden="true">
                        <?php if ($item['artworks'] !== []): ?>
                            <span class="category-slider-mosaic">
                                <?php foreach ($item['artworks'] as $mosaicArtwork): ?>
                                    <img
                                        src="<?= e($mosaicArtwork['src']) ?>"
                                        alt=""
                                        width="<?= (int) $mosaicArtwork['width'] ?>"
                                        height="<?= (int) $mosaicArtwork['height'] ?>"
                                        loading="eager"
                                        decoding="async"
                                        fetchpriority="low"
                                    >
                                <?php endforeach; ?>
                            </span>
                        <?php elseif (is_array($artwork)): ?>
                            <picture>
                                <?php if (! empty($artwork['avif_srcset'])): ?>
                                    <source type="image/avif" srcset="<?= e($artwork['avif_srcset']) ?>" sizes="58px">
                                <?php endif; ?>
                                <?php if (! empty($artwork['webp_srcset'])): ?>
                                    <source type="image/webp" srcset="<?= e($artwork['webp_srcset']) ?>" sizes="58px">
                                <?php endif; ?>
                                <img
                                    src="<?= e($artwork['src']) ?>"
                                    alt=""
                                    width="<?= (int) $artwork['width'] ?>"
                                    height="<?= (int) $artwork['height'] ?>"
                                    loading="eager"
                                    decoding="async"
                                    fetchpriority="low"
                                >
                            </picture>
                        <?php else: ?>
                            <span><?= e(strtoupper(substr((string) $item['label'], 0, 2))) ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="category-slider-copy">
                        <strong><?= e((string) $item['label']) ?></strong>
                        <small><?= number_format((int) $item['count']) ?> prompts</small>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <button
        class="category-slider-control category-slider-control--next"
        type="button"
        data-category-slider-next
        aria-label="Show more categories"
    >
        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m9.5 6 6 6-6 6"></path></svg>
    </button>
</nav>
