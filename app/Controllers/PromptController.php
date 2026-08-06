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
    private const PUBLIC_SORTS = ['newest', 'oldest', 'popular', 'most_copied', 'category'];

    public function index(Request $request): Response
    {
        return $this->listing($request);
    }

    public function category(Request $request, string $category): Response
    {
        $category = strtolower(trim($category));

        if (! in_array($category, Prompt::CATEGORIES, true)) {
            return $this->notFound($request, 'Category not found');
        }

        return $this->listing($request, $category, true);
    }

    public function show(Request $request, string $identifier): Response
    {
        $prompt = Prompt::findPublic($identifier);

        if (! $prompt) {
            return $this->notFound($request, 'Prompt not found');
        }

        $canonicalIdentifier = Prompt::publicIdentifier($prompt);

        if ($identifier !== $canonicalIdentifier) {
            return Response::redirect('/prompts/' . rawurlencode($canonicalIdentifier), 301);
        }

        $related = Prompt::related($prompt);
        $description = SeoService::description((string) $prompt['prompt']);
        $categoryName = SeoService::categoryName((string) $prompt['category']);
        $categoryUrl = SeoService::categoryUrl((string) $prompt['category']);
        $image = SeoService::imageMetadata(
            $prompt['thumbnail_path'] ?? null,
            (string) $prompt['title'] . ' AI image prompt preview'
        );
        $publishedAt = SeoService::isoDate($prompt['generated_at'] ?? $prompt['created_at'] ?? null);
        $modifiedAt = SeoService::isoDate($prompt['updated_at'] ?? null);
        $breadcrumbs = [
            ['name' => 'Home', 'url' => app_url('/')],
            ['name' => 'Prompts', 'url' => app_url('/prompts')],
            ['name' => $categoryName, 'url' => $categoryUrl],
            ['name' => (string) $prompt['title'], 'url' => SeoService::promptUrl($prompt)],
        ];

        return $this->view('prompts/show', [
            'title' => $prompt['title'],
            'metaTitle' => SeoService::promptTitle($prompt),
            'metaDescription' => $description,
            'canonical' => SeoService::promptUrl($prompt),
            'ogType' => 'article',
            'ogImage' => $image['url'] ?? null,
            'ogImageAlt' => $image['alt'] ?? null,
            'ogImageType' => $image['type'] ?? null,
            'ogImageWidth' => $image['width'] ?? null,
            'ogImageHeight' => $image['height'] ?? null,
            'ogPublishedTime' => $publishedAt,
            'ogUpdatedTime' => $modifiedAt,
            'structuredData' => [
                SeoService::promptSchema($prompt),
                SeoService::breadcrumbSchema($breadcrumbs),
            ],
            'showAds' => SeoService::canShowAds(),
            'prompt' => $prompt,
            'related' => $related,
            'styleNotes' => Prompt::decodeStyleNotes($prompt['style_notes'] ?? null),
            'breadcrumbs' => $breadcrumbs,
            'categoryName' => $categoryName,
            'categoryPath' => '/prompts/category/' . rawurlencode((string) $prompt['category']),
            'publishedAt' => $publishedAt,
            'modifiedAt' => $modifiedAt,
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

    private function listing(Request $request, ?string $routeCategory = null, bool $dedicatedCategory = false): Response
    {
        $rawPage = trim((string) $request->query('page', '1'));

        if ($rawPage === '' || ! ctype_digit($rawPage) || (int) $rawPage < 1) {
            return $this->notFound($request, 'Library page not found');
        }

        $page = (int) $rawPage;
        $query = trim((string) $request->query('q', ''));
        $rawCategory = $dedicatedCategory
            ? (string) $routeCategory
            : strtolower(trim((string) $request->query('category', '')));
        $category = in_array($rawCategory, Prompt::CATEGORIES, true) ? $rawCategory : '';
        $rawSort = strtolower(trim((string) $request->query('sort', 'newest')));
        $sort = in_array($rawSort, self::PUBLIC_SORTS, true) ? $rawSort : 'newest';
        $filters = [
            'q' => $query,
            'category' => $category,
            'sort' => $sort,
        ];
        $results = Prompt::publicSearch($filters, $page);

        if (($page > (int) $results['last_page'] && (int) $results['total'] > 0)
            || ($page > 1 && (int) $results['total'] === 0)
            || ($dedicatedCategory && (int) $results['total'] === 0 && $query === '')) {
            return $this->notFound($request, 'Library page not found');
        }

        $hasTemporaryFilter = $query !== ''
            || (! $dedicatedCategory && $rawCategory !== '')
            || $rawSort !== 'newest';
        $noindex = $hasTemporaryFilter || (int) $results['total'] === 0;
        $basePath = $dedicatedCategory
            ? '/prompts/category/' . rawurlencode((string) $routeCategory)
            : '/prompts';
        $cleanCanonical = $category !== '' && ! $dedicatedCategory
            ? SeoService::categoryUrl($category)
            : app_url($basePath);
        $canonical = $noindex
            ? $cleanCanonical
            : SeoService::listingUrl($basePath, $page);
        $categoryName = $category !== '' ? SeoService::categoryName($category) : null;
        $description = $category !== ''
            ? SeoService::categoryDescription($category)
            : 'Search and browse completed AI image prompts by subject, visual style, popularity, and category.';
        $pageSuffix = $page > 1 ? ' - Page ' . $page : '';
        $metaTitle = $categoryName !== null
            ? $categoryName . ' AI Image Prompts' . $pageSuffix . ' | ' . SeoService::siteName()
            : 'AI Image Prompt Library' . $pageSuffix . ' | ' . SeoService::siteName();
        $breadcrumbs = $dedicatedCategory ? [
            ['name' => 'Home', 'url' => app_url('/')],
            ['name' => 'Prompts', 'url' => app_url('/prompts')],
            ['name' => (string) $categoryName, 'url' => app_url($basePath)],
        ] : [];
        $structuredData = [];

        if (! $noindex) {
            $structuredData[] = SeoService::collectionSchema(
                $categoryName !== null ? $categoryName . ' AI Image Prompts' : 'AI Image Prompt Library',
                $description,
                $canonical,
                (int) $results['total'],
                $results['items']
            );
            $structuredData[] = SeoService::websiteSchema();

            if ($breadcrumbs !== []) {
                $structuredData[] = SeoService::breadcrumbSchema($breadcrumbs);
            }
        }

        $categoryCounts = Prompt::publicCategoryCounts();
        $categories = array_keys(array_filter($categoryCounts, static fn (int $count): bool => $count > 0));

        return $this->view('prompts/index', [
            'title' => $categoryName !== null ? $categoryName . ' prompts' : 'Prompt library',
            'metaTitle' => $metaTitle,
            'metaDescription' => $description,
            'canonical' => $canonical,
            'noindex' => $noindex,
            'structuredData' => $structuredData,
            'showAds' => SeoService::canShowAds($noindex, (int) $results['total']),
            'prevUrl' => ! $noindex && $page > 1 ? SeoService::listingUrl($basePath, $page - 1) : null,
            'nextUrl' => ! $noindex && $page < (int) $results['last_page']
                ? SeoService::listingUrl($basePath, $page + 1)
                : null,
            'filters' => $filters,
            'results' => $results,
            'categories' => $categories,
            'categoryCounts' => $categoryCounts,
            'listingEyebrow' => $dedicatedCategory ? 'Prompt category' : 'Prompt library',
            'listingHeading' => $categoryName !== null
                ? $categoryName . ' AI image prompts'
                : 'Search completed prompts',
            'listingIntro' => $description,
            'breadcrumbs' => $breadcrumbs,
            'dedicatedCategory' => $dedicatedCategory,
        ]);
    }

    private function notFound(Request $request, string $title): Response
    {
        return Response::html(
            \App\Core\View::render('public/404', [
                'title' => $title,
                'metaTitle' => 'Page Not Found | MyPromptArt',
                'metaDescription' => 'The requested MyPromptArt page could not be found.',
                'canonical' => app_url($request->path()),
                'noindex' => true,
                'nofollow' => true,
                'showAds' => false,
            ], 'layouts/public'),
            404,
            ['X-Robots-Tag' => 'noindex, nofollow']
        );
    }
}
