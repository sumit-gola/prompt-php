# Prompt Library Core PHP

A production-oriented Core PHP 8.2+ prompt library. Public visitors can browse, search, open, and copy completed prompts only. Admins manage prompt creation, uploads, publishing, queue retries, and deletion.

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
DB_HOST=127.0.0.1
DB_DATABASE=prompt_library
DB_USERNAME=prompt_user
DB_PASSWORD=...
ADSENSE_ENABLED=false
ADSENSE_PUBLISHER_ID=
AI_PROVIDER=none
```

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
- `/prompts/{id-or-slug}`
- `/about`
- `/contact`
- `/privacy-policy`
- `/terms`
- `/robots.txt`
- `/sitemap.xml`
- `/ads.txt`

The public library queries only `status = completed` prompts with non-empty prompt text. Public users cannot upload, generate, edit, or delete content.

## Admin Routes

- `/admin`
- `/admin/prompts`
- `/admin/prompts/create`
- `/admin/prompts/{id}/edit`

All `/admin/*` routes require an authenticated `users.is_admin = 1` account. Registration creates non-admin users by default; create the first admin with the seed script.

## Uploads

Images are stored under `public/storage/prompts`. The included `public/storage/.htaccess` disables PHP execution for Apache. Configure equivalent rules for Nginx or your hosting platform.

Reference-image creation accepts 1-10 JPG, PNG, or WebP files, max 5MB each. The prompt table stores the primary reference image directly and stores the full reference image list in `style_notes.reference_images`.

## SEO and Ads

The app renders canonical URLs, descriptions, Open Graph tags, robots.txt, sitemap.xml, and ads.txt. Ads are shown only on public pages when `ADSENSE_ENABLED=true`, `ADSENSE_PUBLISHER_ID` is set, and the page is not marked noindex or empty.

## Smoke Test

```bash
php tests/smoke.php
```

The smoke test does not require a database. It checks expected public/admin routes, CSRF on POST routes, and admin middleware on admin routes.

