<section class="admin-heading">
    <div>
        <p class="eyebrow">Prompt #<?= (int) $prompt['id'] ?></p>
        <h1><?= e($prompt['title']) ?></h1>
    </div>
    <a class="button button-ghost" href="<?= url('/admin/prompts') ?>">Back</a>
</section>

<?php if (! empty($prompt['error_message'])): ?>
    <div class="flash flash-error"><?= e($prompt['error_message']) ?></div>
<?php endif; ?>

<section class="admin-panel">
    <form class="stack-form two-column-form" method="post" action="<?= url('/admin/prompts/' . $prompt['id']) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label>Title
            <input name="title" type="text" maxlength="255" value="<?= e($prompt['title']) ?>" required>
        </label>
        <label>Slug
            <input name="source_slug" type="text" maxlength="255" value="<?= e($prompt['source_slug']) ?>">
        </label>
        <label>Category
            <select name="category" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category) ?>"<?= selected($prompt['category'], $category) ?>><?= e(ucfirst($category)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Status
            <select name="status">
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= e($status) ?>"<?= selected($prompt['status'], $status) ?>><?= e(ucfirst($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Generation mode
            <select name="generation_mode">
                <?php foreach ($modes as $mode): ?>
                    <option value="<?= e($mode) ?>"<?= selected($prompt['generation_mode'], $mode) ?>><?= e(str_replace('_', ' ', $mode)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>AI provider
            <input name="ai_provider" type="text" value="<?= e($prompt['ai_provider']) ?>">
        </label>
        <label>AI model
            <input name="ai_model" type="text" value="<?= e($prompt['ai_model']) ?>">
        </label>
        <label>Tested with
            <input name="tested_models" type="text" maxlength="500" value="<?= e($prompt['tested_models'] ?? '') ?>" placeholder="Gemini, ChatGPT Image, Midjourney">
        </label>
        <label>Last reviewed
            <input name="reviewed_at" type="datetime-local" value="<?= ! empty($prompt['reviewed_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $prompt['reviewed_at']))) : '' ?>">
        </label>
        <label>Generated at
            <input name="generated_at" type="datetime-local" value="<?= $prompt['generated_at'] ? e(date('Y-m-d\TH:i', strtotime((string) $prompt['generated_at']))) : '' ?>">
        </label>
        <label class="span-2">Prompt
            <textarea name="prompt" rows="8"><?= e($prompt['prompt']) ?></textarea>
        </label>
        <label class="span-2">Negative prompt
            <textarea name="negative_prompt" rows="4"><?= e($prompt['negative_prompt']) ?></textarea>
        </label>
        <label class="span-2">Thumbnail prompt
            <textarea name="thumbnail_prompt" rows="3"><?= e($prompt['thumbnail_prompt']) ?></textarea>
        </label>
        <label>Replace thumbnail
            <input name="thumbnail" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        </label>
        <label>Replace reference image
            <input name="reference_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        </label>
        <label>Source site
            <input name="source_site" type="text" maxlength="255" value="<?= e($prompt['source_site']) ?>">
        </label>
        <label>Source URL
            <input name="source_url" type="url" maxlength="2048" value="<?= e($prompt['source_url']) ?>">
        </label>
        <label>Source thumbnail URL
            <input name="source_thumbnail_url" type="url" maxlength="2048" value="<?= e($prompt['source_thumbnail_url']) ?>">
        </label>
        <label>Source published at
            <input name="source_published_at" type="datetime-local" value="<?= $prompt['source_published_at'] ? e(date('Y-m-d\TH:i', strtotime((string) $prompt['source_published_at']))) : '' ?>">
        </label>
        <label>Source modified at
            <input name="source_modified_at" type="datetime-local" value="<?= $prompt['source_modified_at'] ? e(date('Y-m-d\TH:i', strtotime((string) $prompt['source_modified_at']))) : '' ?>">
        </label>
        <label class="span-2">Source idea
            <textarea name="source_idea" rows="4" maxlength="2000"><?= e($prompt['source_idea']) ?></textarea>
        </label>
        <label class="span-2">Style notes JSON
            <textarea name="style_notes" rows="6"><?= e($styleNotes) ?></textarea>
        </label>
        <label class="span-2">Error message
            <textarea name="error_message" rows="3"><?= e($prompt['error_message']) ?></textarea>
        </label>
        <button class="button span-2" type="submit">Save changes</button>
    </form>
</section>

<section class="admin-panel asset-preview">
    <div>
        <p class="eyebrow">Assets</p>
        <h2>Stored images</h2>
    </div>
    <div class="asset-grid">
        <figure>
            <?php if (! empty($prompt['thumbnail_path'])): ?>
                <img src="<?= asset($prompt['thumbnail_path']) ?>" alt="">
            <?php else: ?>
                <div class="prompt-placeholder">TH</div>
            <?php endif; ?>
            <figcaption>Thumbnail</figcaption>
        </figure>
        <figure>
            <?php if (! empty($prompt['reference_image_path'])): ?>
                <img src="<?= asset($prompt['reference_image_path']) ?>" alt="">
            <?php else: ?>
                <div class="prompt-placeholder">RF</div>
            <?php endif; ?>
            <figcaption>Reference</figcaption>
        </figure>
    </div>
</section>

<section class="danger-zone">
    <form method="post" action="<?= url('/admin/prompts/' . $prompt['id'] . '/regenerate') ?>">
        <?= csrf_field() ?>
        <button class="button button-secondary" type="submit">Regenerate</button>
    </form>
    <form method="post" action="<?= url('/admin/prompts/' . $prompt['id'] . '/delete') ?>" data-confirm="Delete this prompt and stored assets?">
        <?= csrf_field() ?>
        <button class="button button-danger" type="submit">Delete prompt</button>
    </form>
</section>
