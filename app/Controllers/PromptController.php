<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Models\Prompt;
use App\Services\SeoService;

final class PromptController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category' => trim((string) $request->query('category', '')),
            'sort' => trim((string) $request->query('sort', 'newest')),
        ];
        $page = max(1, (int) $request->query('page', 1));
        $results = Prompt::publicSearch($filters, $page);
        $hasFilter = $filters['q'] !== '' || $filters['category'] !== '';
        $noindex = $hasFilter && (int) $results['total'] === 0;

        return $this->view('prompts/index', [
            'title' => 'Prompt library',
            'metaTitle' => 'Search the AI Prompt Library',
            'metaDescription' => 'Search completed AI image prompts by category, subject, style, popularity, and copy count.',
            'canonical' => app_url('/prompts'),
            'noindex' => $noindex,
            'showAds' => SeoService::canShowAds($noindex, (int) $results['total']),
            'filters' => $filters,
            'results' => $results,
            'categories' => Prompt::CATEGORIES,
        ]);
    }

    public function show(Request $request, string $identifier): Response
    {
        $prompt = Prompt::findPublic($identifier);

        if (! $prompt) {
            return $this->view('public/404', ['title' => 'Prompt not found'], 'layouts/public', 404);
        }

        $related = Prompt::related($prompt);
        $description = SeoService::description((string) $prompt['prompt']);

        return $this->view('prompts/show', [
            'title' => $prompt['title'],
            'metaTitle' => $prompt['title'] . ' prompt',
            'metaDescription' => $description,
            'canonical' => SeoService::promptUrl($prompt),
            'ogImage' => $prompt['thumbnail_path'] ? app_url('/' . $prompt['thumbnail_path']) : null,
            'showAds' => SeoService::canShowAds(),
            'prompt' => $prompt,
            'related' => $related,
            'styleNotes' => Prompt::decodeStyleNotes($prompt['style_notes'] ?? null),
        ]);
    }

    public function copy(Request $request, string $id): Response
    {
        if (! ctype_digit($id)) {
            return $this->json(['ok' => false, 'message' => 'Prompt not found.'], 404);
        }

        $key = 'copy:' . sha1($request->ip() . ':' . $id);

        if (! RateLimiter::hit($key, 30, 3600)) {
            return $this->json(['ok' => false, 'message' => 'Copy limit reached. Try again later.'], 429);
        }

        $prompt = Prompt::incrementCopyCount((int) $id);

        if (! $prompt) {
            return $this->json(['ok' => false, 'message' => 'Prompt not found.'], 404);
        }

        return $this->json([
            'ok' => true,
            'prompt' => $prompt['prompt'],
            'copy_count' => (int) $prompt['copy_count'],
        ]);
    }
}

