<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Prompt as PromptModel;
use App\Models\PromptGenerationJob;
use App\Services\ImageService;
use App\Services\StorageService;

final class PromptController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'category' => trim((string) $request->query('category', '')),
            'generation_mode' => trim((string) $request->query('generation_mode', '')),
            'source' => trim((string) $request->query('source', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'sort' => trim((string) $request->query('sort', 'newest')),
        ];

        return $this->adminView('admin/prompts/index', [
            'title' => 'Prompts',
            'filters' => $filters,
            'results' => PromptModel::adminSearch($filters, max(1, (int) $request->query('page', 1))),
            'categories' => PromptModel::CATEGORIES,
            'statuses' => PromptModel::STATUSES,
            'modes' => PromptModel::GENERATION_MODES,
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->adminView('admin/prompts/create', [
            'title' => 'Create prompt',
            'categories' => PromptModel::CATEGORIES,
            'statuses' => PromptModel::STATUSES,
            'modes' => PromptModel::GENERATION_MODES,
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $request->post();
        $mode = (string) ($data['generation_mode'] ?? 'imported');
        $errors = $this->validateCommon($data, true);

        Validator::in($errors, 'generation_mode', $mode, PromptModel::GENERATION_MODES, 'Generation mode');

        if ($mode === 'reference_image') {
            return $this->storeReferenceImagePrompt($request, $data, $errors);
        }

        if ($mode === 'auto') {
            return $this->storeAutoPrompt($data, $errors);
        }

        return $this->storeImportedPrompt($request, $data, $errors);
    }

    public function edit(Request $request, string $id): Response
    {
        $prompt = $this->promptOr404($id);

        if ($prompt instanceof Response) {
            return $prompt;
        }

        return $this->adminView('admin/prompts/edit', [
            'title' => 'Edit prompt',
            'prompt' => $prompt,
            'styleNotes' => json_encode(PromptModel::decodeStyleNotes($prompt['style_notes'] ?? null), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'categories' => PromptModel::CATEGORIES,
            'statuses' => PromptModel::STATUSES,
            'modes' => PromptModel::GENERATION_MODES,
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $prompt = $this->promptOr404($id);

        if ($prompt instanceof Response) {
            return $prompt;
        }

        $data = $request->post();
        $errors = $this->validateCommon($data, false);
        $status = (string) ($data['status'] ?? $prompt['status']);

        Validator::in($errors, 'status', $status, PromptModel::STATUSES, 'Status');
        Validator::in($errors, 'generation_mode', (string) ($data['generation_mode'] ?? ''), PromptModel::GENERATION_MODES, 'Generation mode');

        if ($status === 'completed' && trim((string) ($data['prompt'] ?? '')) === '') {
            $errors['prompt'] = 'Completed prompts must have prompt text.';
        }

        $styleNotes = $this->parseStyleNotes((string) ($data['style_notes'] ?? ''), $errors);

        if ($errors !== []) {
            return $this->backWithErrors($errors, $data);
        }

        $update = [
            'title' => trim((string) $data['title']),
            'prompt' => trim((string) ($data['prompt'] ?? '')),
            'negative_prompt' => trim((string) ($data['negative_prompt'] ?? '')),
            'thumbnail_prompt' => trim((string) ($data['thumbnail_prompt'] ?? '')),
            'generation_mode' => (string) $data['generation_mode'],
            'source_idea' => trim((string) ($data['source_idea'] ?? '')),
            'source_site' => trim((string) ($data['source_site'] ?? '')),
            'source_slug' => $this->normalizeSlug((string) ($data['source_slug'] ?? ''), (string) $data['title'], (int) $prompt['id']),
            'source_url' => trim((string) ($data['source_url'] ?? '')),
            'source_thumbnail_url' => trim((string) ($data['source_thumbnail_url'] ?? '')),
            'source_published_at' => $this->dateTimeOrNull((string) ($data['source_published_at'] ?? '')),
            'source_modified_at' => $this->dateTimeOrNull((string) ($data['source_modified_at'] ?? '')),
            'category' => (string) $data['category'],
            'style_notes' => $styleNotes,
            'ai_provider' => trim((string) ($data['ai_provider'] ?? '')),
            'ai_model' => trim((string) ($data['ai_model'] ?? '')),
            'status' => $status,
            'error_message' => trim((string) ($data['error_message'] ?? '')),
            'generated_at' => $this->dateTimeOrNull((string) ($data['generated_at'] ?? '')),
        ];

        try {
            $thumbnail = $this->optionalImage($request, 'thumbnail');
            $reference = $this->optionalImage($request, 'reference_image');
        } catch (\InvalidArgumentException $exception) {
            return $this->backWithErrors(['upload' => $exception->getMessage()], $data);
        }

        if ($thumbnail) {
            $newThumbnail = StorageService::storeImage($thumbnail, 'prompts');
            StorageService::deletePublicPath($prompt['thumbnail_path'] ?? null);
            $update['thumbnail_path'] = $newThumbnail;
        }

        if ($reference) {
            $newReference = StorageService::storeImage($reference, 'prompts');
            StorageService::deletePublicPath($prompt['reference_image_path'] ?? null);
            $update['reference_image_path'] = $newReference;
            $styleNotes['reference_images'] = [$newReference];
            $update['style_notes'] = $styleNotes;

            if (! $thumbnail && empty($prompt['thumbnail_path'])) {
                $update['thumbnail_path'] = ImageService::thumbnail($newReference) ?: $newReference;
            }
        }

        PromptModel::update((int) $prompt['id'], $update);
        Session::setFlash('success', 'Prompt updated.');

        return $this->redirect('/admin/prompts/' . $prompt['id'] . '/edit');
    }

    public function destroy(Request $request, string $id): Response
    {
        $prompt = $this->promptOr404($id);

        if ($prompt instanceof Response) {
            return $prompt;
        }

        $this->deleteAssets($prompt);
        PromptModel::delete((int) $prompt['id']);
        Session::setFlash('success', 'Prompt deleted.');

        return $this->redirect('/admin/prompts');
    }

    public function regenerate(Request $request, string $id): Response
    {
        $prompt = $this->promptOr404($id);

        if ($prompt instanceof Response) {
            return $prompt;
        }

        $message = $this->queueRegeneration($prompt);

        if ($message !== null) {
            Session::setFlash('error', $message);
            return $this->redirect('/admin/prompts/' . $prompt['id'] . '/edit');
        }

        Session::setFlash('success', 'Prompt regeneration queued.');

        return $this->redirect('/admin/prompts/' . $prompt['id'] . '/edit');
    }

    public function bulk(Request $request): Response
    {
        $data = $request->post();
        $ids = is_array($data['ids'] ?? null) ? $data['ids'] : [];
        $prompts = PromptModel::bulkByIds($ids);

        if ($prompts === []) {
            Session::setFlash('error', 'Select at least one prompt.');
            return $this->redirect('/admin/prompts');
        }

        $action = (string) ($data['bulk_action'] ?? '');

        if ($action === 'delete') {
            foreach ($prompts as $prompt) {
                $this->deleteAssets($prompt);
            }

            $count = PromptModel::bulkDelete($ids);
            Session::setFlash('success', $count . ' prompt' . ($count === 1 ? '' : 's') . ' deleted.');

            return $this->redirect('/admin/prompts');
        }

        if ($action === 'update_status') {
            $status = (string) ($data['bulk_status'] ?? '');

            if (! in_array($status, PromptModel::STATUSES, true)) {
                Session::setFlash('error', 'Choose a valid status.');
                return $this->redirect('/admin/prompts');
            }

            if ($status === 'completed' && $this->hasEmptyPrompt($prompts)) {
                Session::setFlash('error', 'Completed prompts must have prompt text.');
                return $this->redirect('/admin/prompts');
            }

            $count = PromptModel::bulkUpdate($ids, ['status' => $status]);
            Session::setFlash('success', $count . ' prompt' . ($count === 1 ? '' : 's') . ' updated.');

            return $this->redirect('/admin/prompts');
        }

        if ($action === 'update_category') {
            $category = (string) ($data['bulk_category'] ?? '');

            if (! in_array($category, PromptModel::CATEGORIES, true)) {
                Session::setFlash('error', 'Choose a valid category.');
                return $this->redirect('/admin/prompts');
            }

            $count = PromptModel::bulkUpdate($ids, ['category' => $category]);
            Session::setFlash('success', $count . ' prompt' . ($count === 1 ? '' : 's') . ' moved.');

            return $this->redirect('/admin/prompts');
        }

        if ($action === 'retry_generation') {
            $queued = 0;

            foreach ($prompts as $prompt) {
                if ($this->queueRegeneration($prompt) === null) {
                    $queued++;
                }
            }

            Session::setFlash('success', $queued . ' generation job' . ($queued === 1 ? '' : 's') . ' queued.');

            return $this->redirect('/admin/prompts');
        }

        if ($action === 'publish') {
            if ($this->hasEmptyPrompt($prompts)) {
                Session::setFlash('error', 'Only prompts with prompt text can be published.');
                return $this->redirect('/admin/prompts');
            }

            foreach ($prompts as $prompt) {
                PromptModel::update((int) $prompt['id'], [
                    'status' => 'completed',
                    'generated_at' => $prompt['generated_at'] ?: date('Y-m-d H:i:s'),
                ]);
            }

            Session::setFlash('success', count($prompts) . ' prompt' . (count($prompts) === 1 ? '' : 's') . ' published.');

            return $this->redirect('/admin/prompts');
        }

        if ($action === 'draft') {
            $count = PromptModel::bulkUpdate($ids, ['status' => 'draft']);
            Session::setFlash('success', $count . ' prompt' . ($count === 1 ? '' : 's') . ' moved to draft.');

            return $this->redirect('/admin/prompts');
        }

        Session::setFlash('error', 'Choose a bulk action.');

        return $this->redirect('/admin/prompts');
    }

    private function storeReferenceImagePrompt(Request $request, array $data, array $errors): Response
    {
        Validator::max($errors, 'source_idea', $data['source_idea'] ?? '', 2000, 'Reference notes');

        try {
            $files = StorageService::assertReferenceImages($request->files('reference_images'));
        } catch (\InvalidArgumentException $exception) {
            $errors['reference_images'] = $exception->getMessage();
            $files = [];
        }

        if ($errors !== []) {
            return $this->backWithErrors($errors, $data);
        }

        $paths = [];

        foreach ($files as $file) {
            $paths[] = StorageService::storeImage($file, 'prompts');
        }

        $thumbnail = ImageService::thumbnail($paths[0]) ?: $paths[0];
        $prompt = PromptModel::create([
            'user_id' => (int) (Auth::user()['id'] ?? 0),
            'title' => trim((string) $data['title']),
            'generation_mode' => 'reference_image',
            'source_idea' => trim((string) ($data['source_idea'] ?? '')),
            'category' => (string) $data['category'],
            'status' => 'pending',
            'thumbnail_path' => $thumbnail,
            'reference_image_path' => $paths[0],
            'style_notes' => ['reference_images' => $paths],
        ]);

        PromptGenerationJob::create((int) $prompt['id'], 'reference_image', ['reference_images' => $paths]);
        Session::setFlash('success', 'Reference image generation queued.');

        return $this->redirect('/admin/prompts/' . $prompt['id'] . '/edit');
    }

    private function storeAutoPrompt(array $data, array $errors): Response
    {
        Validator::required($errors, 'source_idea', $data['source_idea'] ?? '', 'Text brief');
        Validator::max($errors, 'source_idea', $data['source_idea'] ?? '', 2000, 'Text brief');

        if ($errors !== []) {
            return $this->backWithErrors($errors, $data);
        }

        $prompt = PromptModel::create([
            'user_id' => (int) (Auth::user()['id'] ?? 0),
            'title' => trim((string) $data['title']),
            'generation_mode' => 'auto',
            'source_idea' => trim((string) $data['source_idea']),
            'category' => (string) $data['category'],
            'status' => 'pending',
        ]);

        PromptGenerationJob::create((int) $prompt['id'], 'text_brief', ['brief' => $prompt['source_idea']]);
        Session::setFlash('success', 'Text brief generation queued.');

        return $this->redirect('/admin/prompts/' . $prompt['id'] . '/edit');
    }

    private function storeImportedPrompt(Request $request, array $data, array $errors): Response
    {
        $status = (string) ($data['status'] ?? 'completed');
        Validator::in($errors, 'status', $status, PromptModel::STATUSES, 'Status');

        if ($status === 'completed' && trim((string) ($data['prompt'] ?? '')) === '') {
            $errors['prompt'] = 'Completed prompts must have prompt text.';
        }

        $styleNotes = $this->parseStyleNotes((string) ($data['style_notes'] ?? ''), $errors);

        if ($errors !== []) {
            return $this->backWithErrors($errors, $data);
        }

        $thumbnailPath = null;
        $referencePath = null;
        try {
            $thumbnail = $this->optionalImage($request, 'thumbnail');
            $reference = $this->optionalImage($request, 'reference_image');
        } catch (\InvalidArgumentException $exception) {
            return $this->backWithErrors(['upload' => $exception->getMessage()], $data);
        }

        if ($reference) {
            $referencePath = StorageService::storeImage($reference, 'prompts');
            $styleNotes['reference_images'] = [$referencePath];
        }

        if ($thumbnail) {
            $thumbnailPath = StorageService::storeImage($thumbnail, 'prompts');
        } elseif ($referencePath) {
            $thumbnailPath = ImageService::thumbnail($referencePath) ?: $referencePath;
        }

        $prompt = PromptModel::create([
            'user_id' => (int) (Auth::user()['id'] ?? 0),
            'title' => trim((string) $data['title']),
            'prompt' => trim((string) ($data['prompt'] ?? '')),
            'negative_prompt' => trim((string) ($data['negative_prompt'] ?? '')),
            'thumbnail_prompt' => trim((string) ($data['thumbnail_prompt'] ?? '')),
            'thumbnail_path' => $thumbnailPath,
            'reference_image_path' => $referencePath,
            'generation_mode' => 'imported',
            'source_idea' => trim((string) ($data['source_idea'] ?? '')),
            'source_site' => trim((string) ($data['source_site'] ?? '')),
            'source_slug' => $this->normalizeSlug((string) ($data['source_slug'] ?? ''), (string) $data['title']),
            'source_url' => trim((string) ($data['source_url'] ?? '')),
            'source_thumbnail_url' => trim((string) ($data['source_thumbnail_url'] ?? '')),
            'source_published_at' => $this->dateTimeOrNull((string) ($data['source_published_at'] ?? '')),
            'source_modified_at' => $this->dateTimeOrNull((string) ($data['source_modified_at'] ?? '')),
            'category' => (string) $data['category'],
            'style_notes' => $styleNotes,
            'ai_provider' => trim((string) ($data['ai_provider'] ?? '')),
            'ai_model' => trim((string) ($data['ai_model'] ?? '')),
            'status' => $status,
            'generated_at' => $status === 'completed' ? date('Y-m-d H:i:s') : null,
        ]);

        Session::setFlash('success', 'Prompt created.');

        return $this->redirect('/admin/prompts/' . $prompt['id'] . '/edit');
    }

    private function validateCommon(array $data, bool $create): array
    {
        $errors = [];

        Validator::required($errors, 'title', $data['title'] ?? '', 'Title');
        Validator::max($errors, 'title', $data['title'] ?? '', 255, 'Title');
        Validator::in($errors, 'category', (string) ($data['category'] ?? ''), PromptModel::CATEGORIES, 'Category');

        return $errors;
    }

    private function parseStyleNotes(string $json, array &$errors): array
    {
        if (trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            $errors['style_notes'] = 'Style notes must be valid JSON.';
            return [];
        }

        return $decoded;
    }

    private function optionalImage(Request $request, string $key): ?array
    {
        $files = StorageService::normalizeFiles($request->files($key));

        foreach ($files as $file) {
            if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            StorageService::assertValidImage($file);

            return $file;
        }

        return null;
    }

    private function promptOr404(string $id): array|Response
    {
        if (! ctype_digit($id)) {
            return $this->adminView('admin/404', ['title' => 'Prompt not found'], 404);
        }

        $prompt = PromptModel::find((int) $id);

        if (! $prompt) {
            return $this->adminView('admin/404', ['title' => 'Prompt not found'], 404);
        }

        return $prompt;
    }

    private function deleteAssets(array $prompt): void
    {
        $paths = [
            $prompt['thumbnail_path'] ?? null,
            $prompt['reference_image_path'] ?? null,
        ];

        $styleNotes = PromptModel::decodeStyleNotes($prompt['style_notes'] ?? null);

        foreach (($styleNotes['reference_images'] ?? []) as $path) {
            $paths[] = $path;
        }

        foreach (array_unique(array_filter($paths)) as $path) {
            StorageService::deletePublicPath((string) $path);
        }
    }

    private function queueRegeneration(array $prompt): ?string
    {
        if ((string) $prompt['generation_mode'] === 'imported') {
            return 'Imported prompts are not eligible for regeneration.';
        }

        $styleNotes = PromptModel::decodeStyleNotes($prompt['style_notes'] ?? null);

        if ((string) $prompt['generation_mode'] === 'reference_image') {
            $images = $styleNotes['reference_images'] ?? [];

            if ($images === [] && ! empty($prompt['reference_image_path'])) {
                $images = [$prompt['reference_image_path']];
            }

            if ($images === []) {
                return 'Reference image prompts need at least one stored reference image.';
            }

            PromptGenerationJob::create((int) $prompt['id'], 'reference_image', ['reference_images' => $images]);
        } else {
            $brief = trim((string) ($prompt['source_idea'] ?? ''));

            if ($brief === '') {
                return 'Text brief prompts need a source idea before retrying.';
            }

            PromptGenerationJob::create((int) $prompt['id'], 'text_brief', ['brief' => $brief]);
        }

        PromptModel::update((int) $prompt['id'], [
            'status' => 'pending',
            'error_message' => null,
        ]);

        return null;
    }

    private function hasEmptyPrompt(array $prompts): bool
    {
        foreach ($prompts as $prompt) {
            if (trim((string) ($prompt['prompt'] ?? '')) === '') {
                return true;
            }
        }

        return false;
    }

    private function normalizeSlug(string $slug, string $title, ?int $ignoreId = null): string
    {
        $slug = trim($slug) !== '' ? PromptModel::slugify($slug) : PromptModel::slugify($title);

        return PromptModel::uniqueSlug($slug, $ignoreId);
    }

    private function dateTimeOrNull(string $value): ?string
    {
        $value = trim(str_replace('T', ' ', $value));

        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }
}
