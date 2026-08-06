<?php
$lastPage = max(1, (int) ($results['last_page'] ?? 1));
$currentPage = min(max(1, (int) ($results['page'] ?? 1)), $lastPage);
$total = max(0, (int) ($results['total'] ?? 0));
$perPage = max(1, (int) ($results['per_page'] ?? 1));
$from = $total === 0 ? 0 : (($currentPage - 1) * $perPage) + 1;
$to = min($total, $currentPage * $perPage);
$query = $_GET;
$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

$pageUrl = static function (int $page) use ($query, $path): string {
    $params = $query;

    if ($page <= 1) {
        unset($params['page']);
    } else {
        $params['page'] = $page;
    }

    $queryString = http_build_query($params);

    return ($path !== '' ? $path : '') . ($queryString !== '' ? '?' . $queryString : '');
};

$pages = [1, $lastPage];

for ($page = $currentPage - 1; $page <= $currentPage + 1; $page++) {
    if ($page > 1 && $page < $lastPage) {
        $pages[] = $page;
    }
}

$pages = array_values(array_unique($pages));
sort($pages);

$elements = [];
$previousPage = null;

foreach ($pages as $page) {
    if ($previousPage !== null && $page - $previousPage > 1) {
        if ($page - $previousPage === 2) {
            $elements[] = $previousPage + 1;
        } else {
            $elements[] = 'ellipsis-' . $previousPage . '-' . $page;
        }
    }

    $elements[] = $page;
    $previousPage = $page;
}
?>
<?php if ($lastPage > 1): ?>
    <nav class="pagination-wrap" aria-label="Pagination Navigation">
        <p class="pagination-summary">
            Showing <strong><?= (int) $from ?></strong> to <strong><?= (int) $to ?></strong> of <strong><?= (int) $total ?></strong> results
        </p>

        <div class="pagination-mobile">
            <?php if ($currentPage > 1): ?>
                <a class="pagination-step" href="<?= e($pageUrl($currentPage - 1)) ?>" rel="prev" aria-label="Previous page">Previous</a>
            <?php else: ?>
                <span class="pagination-step disabled" aria-disabled="true">Previous</span>
            <?php endif; ?>

            <?php if ($currentPage < $lastPage): ?>
                <a class="pagination-step" href="<?= e($pageUrl($currentPage + 1)) ?>" rel="next" aria-label="Next page">Next</a>
            <?php else: ?>
                <span class="pagination-step disabled" aria-disabled="true">Next</span>
            <?php endif; ?>
        </div>

        <div class="pagination pagination-desktop">
            <?php if ($currentPage > 1): ?>
                <a class="pagination-step" href="<?= e($pageUrl($currentPage - 1)) ?>" rel="prev" aria-label="Previous page">Previous</a>
            <?php else: ?>
                <span class="pagination-step disabled" aria-disabled="true">Previous</span>
            <?php endif; ?>

            <?php foreach ($elements as $element): ?>
                <?php if (is_int($element)): ?>
                    <?php if ($element === $currentPage): ?>
                        <span class="active" aria-current="page"><?= (int) $element ?></span>
                    <?php else: ?>
                        <a href="<?= e($pageUrl($element)) ?>" aria-label="Go to page <?= (int) $element ?>"><?= (int) $element ?></a>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="pagination-ellipsis" aria-hidden="true">...</span>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($currentPage < $lastPage): ?>
                <a class="pagination-step" href="<?= e($pageUrl($currentPage + 1)) ?>" rel="next" aria-label="Next page">Next</a>
            <?php else: ?>
                <span class="pagination-step disabled" aria-disabled="true">Next</span>
            <?php endif; ?>
        </div>
    </nav>
<?php endif; ?>
