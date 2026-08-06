<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Prompt;
use App\Services\SeoService;

final class PublicController extends Controller
{
    public function home(Request $request): Response
    {
        $prompts = Prompt::latestCompleted(8);

        return $this->view('public/home', [
            'title' => 'MyPromptArt',
            'metaTitle' => 'MyPromptArt - AI Prompt Library for Creators',
            'metaDescription' => 'Search, open, and copy ready-to-use AI image prompts for portraits, fashion, products, lifestyle, and art.',
            'metaKeywords' => 'MyPromptArt, AI image prompts, prompt library, copy prompts, generative AI prompts, image generation prompts',
            'canonical' => app_url('/'),
            'ogImageAlt' => SeoService::defaultShareImageAlt(),
            'structuredData' => [
                SeoService::websiteSchema(),
                SeoService::organizationSchema(),
                SeoService::collectionSchema('Latest AI image prompts', 'Recently published completed AI image prompts ready to open and copy.', count($prompts)),
            ],
            'prompts' => $prompts,
            'categories' => Prompt::CATEGORIES,
            'stats' => Prompt::stats(),
            'showAds' => SeoService::canShowAds(false, count($prompts)),
        ]);
    }

    public function about(Request $request): Response
    {
        return $this->page('About', 'public/about', 'About Prompt Library', 'A public archive of completed AI image prompts curated for browsing and copying.');
    }

    public function contact(Request $request): Response
    {
        return $this->page('Contact', 'public/contact', 'Contact Prompt Library', 'Contact information for Prompt Library editorial and support requests.');
    }

    public function privacy(Request $request): Response
    {
        return $this->page('Privacy policy', 'public/privacy', 'Privacy policy', 'How Prompt Library handles account, usage, and analytics data.');
    }

    public function terms(Request $request): Response
    {
        return $this->page('Terms', 'public/terms', 'Terms of use', 'Terms for browsing and copying prompts from Prompt Library.');
    }

    public function robots(Request $request): Response
    {
        $body = "User-agent: *\n";
        $body .= "Disallow: /admin\n";
        $body .= "Disallow: /login\n";
        $body .= "Disallow: /register\n";
        $body .= 'Sitemap: ' . app_url('/sitemap.xml') . "\n";

        return Response::text($body);
    }

    public function ads(Request $request): Response
    {
        $publisherId = trim((string) env('ADSENSE_PUBLISHER_ID', ''));

        if (! filter_var(env('ADSENSE_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN) || $publisherId === '') {
            return Response::text('');
        }

        return Response::text('google.com, ' . $publisherId . ', DIRECT, f08c47fec0942fa0' . "\n");
    }

    public function sitemap(Request $request): Response
    {
        $pages = ['/', '/prompts', '/about', '/contact', '/privacy-policy', '/terms'];
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($pages as $page) {
            $xml[] = '<url><loc>' . e(app_url($page)) . '</loc><changefreq>weekly</changefreq></url>';
        }

        foreach (Prompt::sitemapCompleted() as $prompt) {
            $lastmod = date('Y-m-d', strtotime((string) ($prompt['updated_at'] ?? $prompt['generated_at'] ?? 'now')));
            $xml[] = '<url><loc>' . e(app_url('/prompts/' . Prompt::publicIdentifier($prompt))) . '</loc><lastmod>' . e($lastmod) . '</lastmod><changefreq>weekly</changefreq></url>';
        }

        $xml[] = '</urlset>';

        return Response::xml(implode("\n", $xml));
    }

    private function page(string $title, string $view, string $metaTitle, string $description): Response
    {
        $canonical = app_url('/' . trim(strtolower(str_replace(' ', '-', $title)), '-'));

        return $this->view($view, [
            'title' => $title,
            'metaTitle' => $metaTitle,
            'metaDescription' => $description,
            'metaKeywords' => 'Prompt Library, AI prompt library, AI image prompts, prompt usage policy',
            'canonical' => $canonical,
            'structuredData' => [
                SeoService::webPageSchema($metaTitle, $description, $canonical),
                SeoService::organizationSchema(),
            ],
            'showAds' => SeoService::canShowAds(),
        ]);
    }
}
