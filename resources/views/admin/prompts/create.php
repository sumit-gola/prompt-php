<section class="admin-heading">
    <div>
        <p class="eyebrow">Prompts</p>
        <h1>Create prompt</h1>
    </div>
    <a class="button button-ghost" href="<?= url('/admin/prompts') ?>">Back</a>
</section>

<div class="create-grid">
    <section class="admin-panel">
        <p class="eyebrow">Reference image</p>
        <h2>Generate from uploads</h2>
        <form class="stack-form" method="post" action="<?= url('/admin/prompts') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="generation_mode" value="reference_image">
            <label>Title
                <input name="title" type="text" maxlength="255" required>
            </label>
            <label>Category
                <select name="category" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category) ?>"><?= e(ucfirst($category)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Reference notes
                <textarea name="source_idea" maxlength="2000" rows="4"></textarea>
            </label>
            <label>Reference images
                <input name="reference_images[]" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple required>
            </label>
            <button class="button" type="submit">Queue generation</button>
        </form>
    </section>

    <section class="admin-panel">
        <p class="eyebrow">Text brief</p>
        <h2>Generate from an idea</h2>
        <form class="stack-form" method="post" action="<?= url('/admin/prompts') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="generation_mode" value="auto">
            <label>Title
                <input name="title" type="text" maxlength="255" required>
            </label>
            <label>Category
                <select name="category" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category) ?>"><?= e(ucfirst($category)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Brief
                <textarea name="source_idea" maxlength="2000" rows="6" required></textarea>
            </label>
            <button class="button" type="submit">Queue generation</button>
        </form>
    </section>
</div>

<section class="admin-panel">
    <p class="eyebrow">Imported</p>
    <h2>Create a saved prompt</h2>
    <form class="stack-form two-column-form" method="post" action="<?= url('/admin/prompts') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="generation_mode" value="imported">
        <label>Title
            <input name="title" type="text" maxlength="255" required>
        </label>
        <label>Category
            <select name="category" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category) ?>"><?= e(ucfirst($category)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Status
            <select name="status">
                <option value="completed">Completed</option>
                <option value="draft">Draft</option>
                <option value="pending">Pending</option>
            </select>
        </label>
        <label>Slug
            <input name="source_slug" type="text" maxlength="255">
        </label>
        <label class="span-2">Prompt
            <textarea name="prompt" rows="7"></textarea>
        </label>
        <label class="span-2">Negative prompt
            <textarea name="negative_prompt" rows="4"></textarea>
        </label>
        <label>Thumbnail image
            <input name="thumbnail" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        </label>
        <label>Reference image
            <input name="reference_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        </label>
        <label>Source site
            <input name="source_site" type="text" maxlength="255">
        </label>
        <label>Source URL
            <input name="source_url" type="url" maxlength="2048">
        </label>
        <label>Source published at
            <input name="source_published_at" type="datetime-local">
        </label>
        <label>Source modified at
            <input name="source_modified_at" type="datetime-local">
        </label>
        <label>Tested with
            <input name="tested_models" type="text" maxlength="500" placeholder="Gemini, ChatGPT Image, Midjourney">
        </label>
        <label>Last reviewed
            <input name="reviewed_at" type="datetime-local">
        </label>
        <label class="span-2">Style notes JSON
            <textarea name="style_notes" rows="5" placeholder='{"lighting":"soft directional","camera":"85mm"}'></textarea>
        </label>
        <button class="button span-2" type="submit">Create prompt</button>
    </form>
</section>
