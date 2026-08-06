# MyPromptArt Core PHP

A production-oriented Core PHP 8.2+ collection of AI prompts. Public visitors can browse, search, open, and copy completed prompts only. Admins manage prompt creation, uploads, publishing, queue retries, and deletion.

## Stack

- Core PHP 8.2+ with a small OOP MVC structure
- MySQL with PDO prepared statements
- Server-rendered PHP templates
- Secure PHP sessions, CSRF tokens, password hashing
- Public storage uploads with GD thumbnail generation when available
- Database-backed generation jobs and rate limiting

## Install

```bash
cd /Users/mac/Desktop/python/image-prompt/php-v2
cp .env.example .env
```

Edit `.env` with your database and app URL. Do not commit `.env`.

```bash
php database/migrate.php
php database/seeds/seed.php --email=admin@example.com --password='change-this-long-password'
php -S 127.0.0.1:8080 -t public public/index.php
```

Open `http://127.0.0.1:8080`.

## Environment

Important values:

```dotenv
APP_URL=https://example.com
APP_ENV=production
SESSION_SECURE=true
DB_HOST=127.0.0.1
DB_DATABASE=prompt_library
DB_USERNAME=prompt_user
DB_PASSWORD=...
ADSENSE_ENABLED=false
ADSENSE_PUBLISHER_ID=
ADSENSE_HOME_SLOT=
ADSENSE_LIBRARY_SLOT=
ADSENSE_DETAIL_SLOT=
AI_PROVIDER=none
CONTACT_EMAIL=hello@mypromptart.com
GOOGLE_SITE_VERIFICATION=
BING_SITE_VERIFICATION=
```

For the live site, set `APP_URL=https://mypromptart.com`. Production startup rejects a missing or non-HTTPS `APP_URL`, and public GET/HEAD requests are redirected to the configured scheme and host. The included Apache `public/.htaccess` also sends `www.mypromptart.com` directly to the non-www HTTPS origin with a `301`, preserving the request path and query string. Keep verification tokens and contact details in `.env`; do not hardcode or commit them.

`AI_PROVIDER=none` causes queued jobs to fail gracefully with an admin-visible error. Use `AI_PROVIDER=mock` for local queue testing. Real AI providers should be added behind `App\Services\Ai\AiProviderInterface`.

## Queue Worker

Run one job:

```bash
php scripts/queue-worker.php --once
```

Run continuously:

```bash
php scripts/queue-worker.php --sleep=5 --limit=500
```

Use Supervisor, systemd, cron, or your hosting control panel to keep the worker running in production.

## Public Routes

- `/`
- `/prompts`
- `/ai-prompts/{category}`
- `/prompts/{slug}`
- `/about`
- `/contact`
- `/privacy-policy`
- `/terms`
- `/robots.txt`
- `/sitemap.xml`
- `/ads.txt`

The public library queries only `status = completed` prompts with non-empty prompt text. Public users cannot upload, generate, edit, or delete content.

Category landing pages use clean, indexable `/ai-prompts/{category}` URLs with category-specific titles, headings, descriptions, introductory copy, breadcrumbs, and structured data. Legacy `/prompts/category/{category}` URLs redirect permanently. Search, category-filter, sort, and other non-pagination query combinations remain `noindex,follow` and canonicalize to a clean landing page.

Prompt detail URLs use title-based slugs. Running `php database/migrate.php` backfills missing slugs for legacy records; old numeric prompt URLs remain available only as permanent `301` redirects to their canonical slug URLs.

## Admin Routes

- `/admin`
- `/admin/prompts`
- `/admin/prompts/create`
- `/admin/prompts/{id}/edit`

All `/admin/*` routes require an authenticated `users.is_admin = 1` account. Registration creates non-admin users by default; create the first admin with the seed script.

## Uploads

Images are stored under `public/storage/prompts`. The included `public/storage/.htaccess` disables PHP execution for Apache. Configure equivalent rules for Nginx or your hosting platform.

Reference-image creation accepts 1-10 JPG, PNG, or WebP files, max 5MB each. The prompt table stores the primary reference image directly and stores the full reference image list in `style_notes.reference_images`.

Generate descriptive WebP/AVIF prompt images and responsive 480px/960px variants with a dry run first:

```bash
php scripts/optimize-prompt-images.php
php scripts/optimize-prompt-images.php --apply
```

The public templates use intrinsic dimensions, descriptive alt text, `picture`/`srcset`, lazy-loaded card images, and an eager high-priority detail image. Use `--force` only when existing optimized variants must be rebuilt.

## SEO and Ads

The app renders unique titles and descriptions, absolute canonical URLs, Open Graph and Twitter cards, JSON-LD, visible breadcrumbs, `robots.txt`, image-aware sitemaps, and `ads.txt`.

- `/`, trust pages, clean library pagination, populated category pages, and completed prompt details are indexable.
- Search, sort, and query-string filter combinations are `noindex,follow` and canonicalize to their clean library or category page.
- Admin, auth, forbidden, missing, and server-error responses are `noindex,nofollow`.
- Sitemaps include only completed prompts with non-empty prompt text and a canonical slug. At more than 50,000 public URLs, `/sitemap.xml` automatically becomes a sitemap index with 45,000-prompt chunks under `/sitemaps/*`.
- Prompt detail pages disclose the curator, publication date, explicitly recorded model tests, last editorial review, and source link when available. Admins maintain `tested_models` and `reviewed_at`; the site never infers testing from generation-provider metadata.
- Ads are shown only when `ADSENSE_ENABLED=true`, `ADSENSE_PUBLISHER_ID` is configured, and the page is indexable with non-empty results.

### Google AdSense onboarding

AdSense uses two forms of the same account identifier. Configure the `pub-` value once; the application derives the `ca-pub-` client value used by the verification meta tag and JavaScript loader. For MyPromptArt production:

```dotenv
ADSENSE_ENABLED=true
ADSENSE_PUBLISHER_ID=pub-9410767301492911
ADSENSE_HOME_SLOT=
ADSENSE_LIBRARY_SLOT=
ADSENSE_DETAIL_SLOT=
```

Leave the three slot values empty until real numeric ad-unit IDs have been created in AdSense. With no manual slots, eligible content pages load only the site-level code used for connection verification and dashboard-controlled Auto ads. The `/ads.txt` route independently publishes `google.com, pub-9410767301492911, DIRECT, f08c47fec0942fa0` whenever the publisher ID is valid.

Manual AdSense steps after deploying the code:

1. Add `mypromptart.com` in AdSense and open **Connect your site to AdSense**.
2. Select the AdSense code-snippet verification method.
3. Confirm the live homepage contains `ca-pub-9410767301492911` and `/ads.txt` contains the exact `pub-9410767301492911` seller record.
4. Confirm implementation in AdSense, click **Verify**, and then click **Request review**.
5. Complete payment and account information personally.
6. In **Privacy & messaging**, configure a Google-certified consent-management platform. Google's European regulations message should offer Consent, Do not consent, and Manage options.
7. Enable Auto ads or add real manual ad-unit IDs only after approval.

Do not click live ads or ask other people to click them. Advertising stays disabled on private, authentication, error, noindex, and empty-result pages. The Privacy Policy describes the intended Google advertising integration, but the site owner remains responsible for consent configuration and applicable legal requirements.

### Search Console setup

1. Set `APP_URL=https://mypromptart.com`, `APP_ENV=production`, and `SESSION_SECURE=true` in the production `.env`.
2. Add the Search Console HTML-tag token as `GOOGLE_SITE_VERIFICATION`. Add a Bing token as `BING_SITE_VERIFICATION` when needed.
3. Confirm `https://mypromptart.com/robots.txt` references `https://mypromptart.com/sitemap.xml`.
4. Submit `https://mypromptart.com/sitemap.xml` in Google Search Console and Bing Webmaster Tools.
5. Inspect the homepage, `/prompts`, one category, and several prompt detail URLs. Request indexing only after the live URL test succeeds.
6. Validate prompt and breadcrumb JSON-LD with Google Rich Results Test, then monitor indexing, structured-data reports, Core Web Vitals, and crawl errors after template releases.

Social platforms cache previews. After changing a prompt thumbnail or the default 1200x630 share card, use each platform's sharing debugger or wait for its cache to refresh.

## Smoke Test

```bash
php tests/smoke.php
php tests/seo.php
php tests/adsense.php
```

These tests do not require a database. They check expected public/admin routes, route ordering, CSRF, admin middleware, canonical helpers, metadata escaping, robots directives, structured data, HTTP status handling, AdSense identifier normalization, safe placements, and the exact ads.txt response.

For read-only integration checks, start the local app and run:

```bash
SEO_TEST_BASE_URL=http://127.0.0.1:8080 php tests/http-seo.php
SEO_TEST_BASE_URL=http://127.0.0.1:8080 SEO_TEST_FULL_SITEMAP=true php tests/http-seo.php
```

The HTTP suite uses the configured database without writing to it. It verifies homepage/library/category/prompt metadata, self-canonical pagination, noindex search results, sitemap XML, social crawler output, real 404 responses, auth headers, and exclusion of a non-public prompt when one exists. The optional full-sitemap mode requests every prompt URL and verifies HTTP 200, self-canonical markup, indexability, and response headers.
