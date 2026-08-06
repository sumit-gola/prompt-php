<?php
$imageLoading = $imageLoading ?? 'lazy';
$imageFetchPriority = $imageFetchPriority ?? 'auto';
$imageSizes = $imageSizes ?? '100vw';
?>
<picture>
    <?php if (! empty($image['avif_srcset'])): ?>
        <source
            type="image/avif"
            srcset="<?= e($image['avif_srcset']) ?>"
            sizes="<?= e($imageSizes) ?>"
        >
    <?php endif; ?>
    <?php if (! empty($image['webp_srcset'])): ?>
        <source
            type="image/webp"
            srcset="<?= e($image['webp_srcset']) ?>"
            sizes="<?= e($imageSizes) ?>"
        >
    <?php endif; ?>
    <img
        src="<?= e($image['src']) ?>"
        <?php if (! empty($image['webp_srcset'])): ?>srcset="<?= e($image['webp_srcset']) ?>"<?php endif; ?>
        sizes="<?= e($imageSizes) ?>"
        alt="<?= e($image['alt']) ?>"
        width="<?= (int) $image['width'] ?>"
        height="<?= (int) $image['height'] ?>"
        loading="<?= e($imageLoading) ?>"
        fetchpriority="<?= e($imageFetchPriority) ?>"
        decoding="async"
    >
</picture>
