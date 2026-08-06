<section class="admin-heading">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Prompts</h1>
        <p class="muted"><?= (int) $results['total'] ?> record<?= (int) $results['total'] === 1 ? '' : 's' ?></p>
    </div>
    <a class="button" href="<?= url('/admin/prompts/create') ?>">Create prompt</a>
</section>

<form class="admin-filters" method="get" action="<?= url('/admin/prompts') ?>">
    <label>Search
        <input name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Title, prompt, source">
    </label>
    <label>Status
        <select name="status">
            <option value="">Any</option>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= e($status) ?>"<?= selected($filters['status'], $status) ?>><?= e(ucfirst($status)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Category
        <select name="category">
            <option value="">Any</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= e($category) ?>"<?= selected($filters['category'], $category) ?>><?= e(ucfirst($category)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Mode
        <select name="generation_mode">
            <option value="">Any</option>
            <?php foreach ($modes as $mode): ?>
                <option value="<?= e($mode) ?>"<?= selected($filters['generation_mode'], $mode) ?>><?= e(str_replace('_', ' ', $mode)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Source
        <input name="source" type="search" value="<?= e($filters['source']) ?>">
    </label>
    <label>From
        <input name="date_from" type="date" value="<?= e($filters['date_from']) ?>">
    </label>
    <label>To
        <input name="date_to" type="date" value="<?= e($filters['date_to']) ?>">
    </label>
    <label>Sort
        <select name="sort">
            <option value="newest"<?= selected($filters['sort'], 'newest') ?>>Newest</option>
            <option value="oldest"<?= selected($filters['sort'], 'oldest') ?>>Oldest</option>
            <option value="copies"<?= selected($filters['sort'], 'copies') ?>>Copies</option>
            <option value="title"<?= selected($filters['sort'], 'title') ?>>Title</option>
            <option value="status"<?= selected($filters['sort'], 'status') ?>>Status</option>
            <option value="category"<?= selected($filters['sort'], 'category') ?>>Category</option>
            <option value="generated"<?= selected($filters['sort'], 'generated') ?>>Generated</option>
        </select>
    </label>
    <button class="button" type="submit">Filter</button>
</form>

<form method="post" action="<?= url('/admin/prompts/bulk') ?>" class="admin-panel">
    <?= csrf_field() ?>
    <div class="bulk-bar">
        <select name="bulk_action">
            <option value="">Bulk action</option>
            <option value="publish">Publish</option>
            <option value="draft">Move to draft</option>
            <option value="retry_generation">Retry generation</option>
            <option value="update_status">Update status</option>
            <option value="update_category">Update category</option>
            <option value="delete">Delete</option>
        </select>
        <select name="bulk_status">
            <option value="">Status</option>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= e($status) ?>"><?= e(ucfirst($status)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="bulk_category">
            <option value="">Category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= e($category) ?>"><?= e(ucfirst($category)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button button-secondary" type="submit">Apply</button>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" data-check-all></th>
                    <th>Prompt</th>
                    <th>Status</th>
                    <th>Category</th>
                    <th>Mode</th>
                    <th>Source</th>
                    <th>Copies</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results['items'] as $prompt): ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?= (int) $prompt['id'] ?>"></td>
                        <td>
                            <strong><?= e($prompt['title']) ?></strong>
                            <small><?= e(str_limit((string) $prompt['prompt'], 90)) ?></small>
                        </td>
                        <td><span class="status status-<?= e($prompt['status']) ?>"><?= e($prompt['status']) ?></span></td>
                        <td><?= e($prompt['category']) ?></td>
                        <td><?= e(str_replace('_', ' ', (string) $prompt['generation_mode'])) ?></td>
                        <td><?= e($prompt['source_site'] ?: $prompt['source_slug'] ?: '-') ?></td>
                        <td><?= (int) $prompt['copy_count'] ?></td>
                        <td><?= e((string) $prompt['updated_at']) ?></td>
                        <td><a class="button button-small button-ghost" href="<?= url('/admin/prompts/' . $prompt['id'] . '/edit') ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php require base_path('resources/views/partials/pagination.php'); ?>
</form>

