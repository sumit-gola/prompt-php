<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Prompt;
use App\Services\AdSenseService;
use App\Services\PromptImageService;
use App\Services\SeoService;

final class PublicController extends Controller
{
    private const SITEMAP_CHUNK_SIZE = 45000;

    private const PUBLIC_PAGES = [
        '/',
        '/prompts',
        '/about',
        '/contact',
        '/privacy-policy',
        '/terms',
    ];

    public function home(Request $request): Response
    {
        $prompts = Prompt::latestCompleted(8);
        $stats = Prompt::stats();
        $categoryCounts = Prompt::publicCategoryCounts();
        $categories = array_keys(array_filter($categoryCounts, static fn (int $count): bool => $count > 0));
        $publicCompletedCount = array_sum($categoryCounts);
        $description = 'Discover 1,000+ curated AI image and photo-editing prompts for portraits, fashion, products, art and lifestyle. Preview, copy and create better AI images.';

        return $this->view('public/home', [
            'title' => 'MyPromptArt',
            'metaTitle' => 'AI Image & Photo Editing Prompts | MyPromptArt',
            'metaDescription' => $description,
            'canonical' => app_url('/'),
            'ogImageAlt' => SeoService::defaultShareImageAlt(),
            'structuredData' => [
                SeoService::websiteSchema(),
                SeoService::organizationSchema(),
                SeoService::collectionSchema(
                    'Latest AI image prompts',
                    'Recently published completed AI image prompts ready to open and copy.',
                    app_url('/'),
                    $publicCompletedCount,
                    $prompts
                ),
            ],
            'prompts' => $prompts,
            'categories' => $categories,
            'categoryCounts' => $categoryCounts,
            'publicCompletedCount' => $publicCompletedCount,
            'stats' => $stats,
            'showAds' => SeoService::canShowAds(false, count($prompts)),
            'adPlacement' => 'home',
        ]);
    }

    public function about(Request $request): Response
    {
        return $this->page(
            'About MyPromptArt',
            'public/about',
            '/about',
            'About MyPromptArt | Curated AI Image Prompts',
            'Learn how MyPromptArt curates completed AI image prompts for public browsing, searching, and copying.'
        );
    }

    public function contact(Request $request): Response
    {
        return $this->page(
            'Contact MyPromptArt',
            'public/contact',
            '/contact',
            'Contact MyPromptArt | Editorial and Support',
            'Contact MyPromptArt about editorial questions, prompt sources, licensing, privacy, or account support.',
            ['contactEmail' => trim((string) env('CONTACT_EMAIL', 'hello@mypromptart.com')) ?: 'hello@mypromptart.com']
        );
    }

    public function privacy(Request $request): Response
    {
        return $this->page(
            'Privacy policy',
            'public/privacy',
            '/privacy-policy',
            'Privacy Policy | MyPromptArt',
            'Read how MyPromptArt handles account information, sessions, copy-rate protection, analytics, and advertising data.'
        );
    }

    public function terms(Request $request): Response
    {
        return $this->page(
            'Terms of use',
            'public/terms',
            '/terms',
            'Terms of Use | MyPromptArt',
            'Read the terms for browsing, opening, and copying completed AI image prompts from MyPromptArt.'
        );
    }

    public function robots(Request $request): Response
    {
        $body = "User-agent: *\n";
        $body .= "Allow: /\n";
        $body .= "Disallow: /admin\n";
        $body .= "Disallow: /login\n";
        $body .= "Disallow: /register\n";
        $body .= "Disallow: /scripts\n";
        $body .= 'Sitemap: ' . app_url('/sitemap.xml') . "\n";

        return Response::text($body, 200, ['Cache-Control' => 'public, max-age=3600']);
    }

    public function ads(Request $request): Response
    {
        $line = AdSenseService::adsTxtLine();

        return Response::text(
            $line !== null ? $line . "\n" : '',
            200,
            ['Cache-Control' => 'public, max-age=3600']
        );
    }

    public function sitemap(Request $request): Response
    {
        $promptCount = Prompt::sitemapCount();
        $categoryCount = count(array_filter(Prompt::publicCategoryCounts()));
        $publicUrlCount = count(self::PUBLIC_PAGES) + $categoryCount + $promptCount;

        if ($publicUrlCount <= 50000) {
            return $this->urlSetResponse(
                array_merge($this->pageSitemapEntries(), $this->promptSitemapEntries(1, 50000))
            );
        }

        $pages = max(1, (int) ceil($promptCount / self::SITEMAP_CHUNK_SIZE));
        $xml = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
            '<sitemap><loc>' . SeoService::xml(app_url('/sitemaps/pages.xml')) . '</loc></sitemap>',
        ];

        for ($page = 1; $page <= $pages; $page++) {
            $xml[] = '<sitemap><loc>'
                . SeoService::xml(app_url('/sitemaps/prompts-' . $page . '.xml'))
                . '</loc></sitemap>';
        }

        $xml[] = '</sitemapindex>';

        return $this->xmlResponse(implode("\n", $xml));
    }

    public function sitemapPages(Request $request): Response
    {
        return $this->urlSetResponse($this->pageSitemapEntries());
    }

    public function sitemapPrompts(Request $request, string $page): Response
    {
        if (! ctype_digit($page) || (int) $page < 1) {
            return Response::xml($this->emptyUrlSet(), 404, ['X-Robots-Tag' => 'noindex']);
        }

        $pageNumber = (int) $page;
        $lastPage = max(1, (int) ceil(Prompt::sitemapCount() / self::SITEMAP_CHUNK_SIZE));

        if ($pageNumber > $lastPage) {
            return Response::xml($this->emptyUrlSet(), 404, ['X-Robots-Tag' => 'noindex']);
        }

        return $this->urlSetResponse($this->promptSitemapEntries($pageNumber));
    }

    private function page(
        string $title,
        string $view,
        string $path,
        string $metaTitle,
        string $description,
        array $data = []
    ): Response {
        $canonical = app_url($path);

        return $this->view($view, array_merge($data, [
            'title' => $title,
            'metaTitle' => $metaTitle,
            'metaDescription' => $description,
            'canonical' => $canonical,
            'structuredData' => [
                SeoService::webPageSchema($metaTitle, $description, $canonical),
                SeoService::websiteSchema(),
                SeoService::organizationSchema(),
            ],
            'showAds' => SeoService::canShowAds(),
            'adPlacement' => null,
        ]));
    }

    private function pageSitemapEntries(): array
    {
        $entries = array_map(
            static fn (string $path): array => ['loc' => app_url($path)],
            self::PUBLIC_PAGES
        );

        foreach (Prompt::publicCategoryCounts() as $category => $count) {
            if ($count > 0) {
                $entries[] = ['loc' => SeoService::categoryUrl($category)];
            }
        }

        return $entries;
    }

    private function promptSitemapEntries(int $page, int $limit = self::SITEMAP_CHUNK_SIZE): array
    {
        $offset = ($page - 1) * $limit;
        $entries = [];

        foreach (Prompt::sitemapCompleted($limit, $offset) as $prompt) {
            $timestamp = strtotime((string) ($prompt['updated_at'] ?? $prompt['generated_at'] ?? ''));
            $image = PromptImageService::metadata($prompt, false);
            $entry = [
                'loc' => SeoService::promptUrl($prompt),
                'lastmod' => $timestamp ? date('Y-m-d', $timestamp) : null,
                'image' => $image['url'] ?? null,
            ];
            $entries[] = $entry;
        }

        return $entries;
    }

    private function urlSetResponse(array $entries): Response
    {
        $xml = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">',
        ];

        foreach ($entries as $entry) {
            $url = '<url><loc>' . SeoService::xml((string) $entry['loc']) . '</loc>';

            if (! empty($entry['lastmod'])) {
                $url .= '<lastmod>' . SeoService::xml((string) $entry['lastmod']) . '</lastmod>';
            }

            if (! empty($entry['image'])) {
                $url .= '<image:image><image:loc>' . SeoService::xml((string) $entry['image']) . '</image:loc>';
                $url .= '</image:image>';
            }

            $xml[] = $url . '</url>';
        }

        $xml[] = '</urlset>';

        return $this->xmlResponse(implode("\n", $xml));
    }

    private function xmlResponse(string $xml): Response
    {
        return Response::xml($xml, 200, ['Cache-Control' => 'public, max-age=3600']);
    }

    private function emptyUrlSet(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
    }
}
