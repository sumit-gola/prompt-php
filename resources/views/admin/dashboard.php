<section class="admin-heading">
    <div>
        <p class="eyebrow">Overview</p>
        <h1>Dashboard</h1>
    </div>
    <a class="button" href="<?= url('/admin/prompts/create') ?>">Create prompt</a>
</section>

<section class="stat-grid">
    <div class="stat-card"><span>Total</span><strong><?= (int) $stats['total'] ?></strong></div>
    <div class="stat-card"><span>Completed</span><strong><?= (int) $stats['completed'] ?></strong></div>
    <div class="stat-card"><span>Pending</span><strong><?= (int) $stats['pending'] ?></strong></div>
    <div class="stat-card"><span>Processing</span><strong><?= (int) $stats['processing'] ?></strong></div>
    <div class="stat-card"><span>Failed</span><strong><?= (int) $stats['failed'] ?></strong></div>
    <div class="stat-card"><span>Copies</span><strong><?= (int) $stats['copies'] ?></strong></div>
</section>

<section class="admin-panel">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Queue</p>
            <h2><?= (int) $pendingJobs ?> pending job<?= (int) $pendingJobs === 1 ? '' : 's' ?></h2>
        </div>
        <code>php scripts/queue-worker.php --once</code>
    </div>
</section>

<section class="admin-panel">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Recent</p>
            <h2>Latest prompt records</h2>
        </div>
        <a class="button button-ghost" href="<?= url('/admin/prompts') ?>">Manage</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Category</th>
                    <th>Mode</th>
                    <th>Copies</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $prompt): ?>
                    <tr>
                        <td><a href="<?= url('/admin/prompts/' . $prompt['id'] . '/edit') ?>"><?= e($prompt['title']) ?></a></td>
                        <td><span class="status status-<?= e($prompt['status']) ?>"><?= e($prompt['status']) ?></span></td>
                        <td><?= e($prompt['category']) ?></td>
                        <td><?= e($prompt['generation_mode']) ?></td>
                        <td><?= (int) $prompt['copy_count'] ?></td>
                        <td><?= e((string) $prompt['updated_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

