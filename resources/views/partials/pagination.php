<?php
$lastPage = (int) ($results['last_page'] ?? 1);
$currentPage = (int) ($results['page'] ?? 1);
$query = $_GET;
?>
<?php if ($lastPage > 1): ?>
    <nav class="pagination" aria-label="Pagination">
        <?php for ($page = 1; $page <= $lastPage; $page++): ?>
            <?php $query['page'] = $page; ?>
            <a class="<?= $page === $currentPage ? 'active' : '' ?>" href="?<?= e(http_build_query($query)) ?>"><?= e($page) ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>

