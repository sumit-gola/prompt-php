<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Models\Prompt;
use App\Services\PromptImageService;
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
        $originalCategory = trim($category);
        $category = strtolower($originalCategory);

        if (! in_array($category, Prompt::CATEGORIES, true)) {
            return $this->notFound($request, 'Category not found');
        }

        if ($originalCategory !== $category) {
            return $this->categoryRedirect($request, $category);
        }

        return $this->listing($request, $category, true);
    }

    public function legacyCategory(Request $request, string $category): Response
    {
        $category = strtolower(trim($category));

        if (! in_array($category, Prompt::CATEGORIES, true)) {
            return $this->notFound($request, 'Category not found');
        }

        return $this->categoryRedirect($request, $category);
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
        $image = PromptImageService::metadata($prompt);
        $publishedAt = SeoService::isoDate($prompt['generated_at'] ?? $prompt['created_at'] ?? null);
        $modifiedAt = SeoService::isoDate($prompt['updated_at'] ?? null);
        $reviewedAt = SeoService::isoDate($prompt['reviewed_at'] ?? null);
        $testedModels = trim((string) ($prompt['tested_models'] ?? ''));
        $sourceUrl = trim((string) ($prompt['source_url'] ?? ''));

        if (filter_var($sourceUrl, FILTER_VALIDATE_URL) === false
            || preg_match('#^https?://#i', $sourceUrl) !== 1) {
            $sourceUrl = null;
        }

        $sourceLabel = trim((string) ($prompt['source_site'] ?? ''));

        if ($sourceLabel === '' && $sourceUrl !== null) {
            $sourceLabel = (string) (parse_url($sourceUrl, PHP_URL_HOST) ?: 'Original source');
        }
        $breadcrumbs = [
            ['name' => SeoService::siteName(), 'url' => app_url('/')],
            ['name' => SeoService::categoryHeading((string) $prompt['category']), 'url' => $categoryUrl],
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
                SeoService::organizationSchema(),
            ],
            'showAds' => SeoService::canShowAds(),
            'adPlacement' => 'detail',
            'prompt' => $prompt,
            'promptImage' => $image,
            'related' => $related,
            'styleNotes' => Prompt::decodeStyleNotes($prompt['style_notes'] ?? null),
            'breadcrumbs' => $breadcrumbs,
            'categoryName' => $categoryName,
            'categoryPath' => '/ai-prompts/' . rawurlencode((string) $prompt['category']),
            'publishedAt' => $publishedAt,
            'modifiedAt' => $modifiedAt,
            'reviewedAt' => $reviewedAt,
            'testedModels' => $testedModels,
            'sourceUrl' => $sourceUrl,
            'sourceLabel' => $sourceLabel,
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

        $hasTemporaryFilter = array_diff(array_keys($request->queryParams()), ['page']) !== [];
        $noindex = $hasTemporaryFilter || (int) $results['total'] === 0;
        $basePath = $dedicatedCategory
            ? '/ai-prompts/' . rawurlencode((string) $routeCategory)
            : '/prompts';
        $cleanCanonical = $category !== '' && ! $dedicatedCategory
            ? SeoService::categoryUrl($category)
            : app_url($basePath);
        $canonical = $noindex
            ? $cleanCanonical
            : SeoService::listingUrl($basePath, $page);
        $categoryName = $category !== '' ? SeoService::categoryName($category) : null;
        $metaDescription = $category !== ''
            ? SeoService::categoryMetaDescription($category, (int) $results['total'])
            : 'Search and browse completed AI image prompts by subject, visual style, popularity, and category.';
        $listingIntro = $category !== ''
            ? SeoService::categoryIntro($category)
            : $metaDescription;
        $pageSuffix = $page > 1 ? ' - Page ' . $page : '';
        $metaTitle = $categoryName !== null
            ? SeoService::categoryMetaTitle($category) . $pageSuffix . ' | ' . SeoService::siteName()
            : 'AI Image Prompts' . $pageSuffix . ' | ' . SeoService::siteName();
        $breadcrumbs = $dedicatedCategory ? [
            ['name' => SeoService::siteName(), 'url' => app_url('/')],
            ['name' => SeoService::categoryHeading($category), 'url' => app_url($basePath)],
        ] : [];
        $structuredData = [];

        if (! $noindex) {
            $structuredData[] = SeoService::collectionSchema(
                $categoryName !== null ? $categoryName . ' AI Image Prompts' : 'MyPromptArt AI Image Prompts',
                $metaDescription,
                $canonical,
                (int) $results['total'],
                $results['items']
            );
            $structuredData[] = SeoService::websiteSchema();
            $structuredData[] = SeoService::organizationSchema();

            if ($breadcrumbs !== []) {
                $structuredData[] = SeoService::breadcrumbSchema($breadcrumbs);
            }
        }

        $categoryCounts = Prompt::publicCategoryCounts();
        $categories = array_keys(array_filter($categoryCounts, static fn (int $count): bool => $count > 0));
        $categoryArtwork = [];

        foreach (Prompt::publicCategoryPreviews() as $previewCategory => $previewPrompt) {
            $categoryArtwork[$previewCategory] = is_array($previewPrompt)
                ? PromptImageService::metadata($previewPrompt)
                : null;
        }

        return $this->view('prompts/index', [
            'title' => $categoryName !== null ? $categoryName . ' prompts' : SeoService::siteName() . ' library',
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'canonical' => $canonical,
            'noindex' => $noindex,
            'structuredData' => $structuredData,
            'showAds' => SeoService::canShowAds($noindex, (int) $results['total']),
            'adPlacement' => 'library',
            'prevUrl' => ! $noindex && $page > 1 ? SeoService::listingUrl($basePath, $page - 1) : null,
            'nextUrl' => ! $noindex && $page < (int) $results['last_page']
                ? SeoService::listingUrl($basePath, $page + 1)
                : null,
            'filters' => $filters,
            'results' => $results,
            'categories' => $categories,
            'categoryCounts' => $categoryCounts,
            'categoryArtwork' => $categoryArtwork,
            'listingEyebrow' => $dedicatedCategory ? 'Curated AI prompt collection' : SeoService::siteName() . ' library',
            'listingHeading' => $categoryName !== null
                ? SeoService::categoryHeading($category)
                : 'Search completed prompts',
            'listingIntro' => $listingIntro,
            'breadcrumbs' => $breadcrumbs,
            'dedicatedCategory' => $dedicatedCategory,
            'bodyClass' => 'gallery-page',
        ]);
    }

    private function categoryRedirect(Request $request, string $category): Response
    {
        $target = '/ai-prompts/' . rawurlencode($category);
        $query = http_build_query($request->queryParams());

        return Response::redirect($query !== '' ? $target . '?' . $query : $target, 301);
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
