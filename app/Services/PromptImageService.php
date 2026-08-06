<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Prompt;

final class PromptImageService
{
    private const OUTPUT_DIRECTORY = 'storage/prompts/images';

    private const RESPONSIVE_WIDTHS = [480, 960];

    private const WEBP_QUALITY = 84;

    private const AVIF_QUALITY = 58;

    public static function metadata(array $prompt, bool $generate = false): ?array
    {
        $canonicalPath = self::plannedPath($prompt);

        if (is_file(public_path($canonicalPath))) {
            return self::metadataForPath($canonicalPath, $prompt);
        }

        if ($generate) {
            $optimized = self::optimize($prompt);

            if ($optimized !== null) {
                return $optimized;
            }
        }

        $path = self::localRelativePath((string) ($prompt['thumbnail_path'] ?? ''));

        return $path !== null ? self::metadataForPath($path, $prompt) : null;
    }

    public static function optimize(array $prompt, ?string $preferredSource = null, bool $force = false): ?array
    {
        $slug = self::imageSlug($prompt);
        $canonicalPath = self::OUTPUT_DIRECTORY . '/' . $slug . '.webp';
        $canonicalAbsolutePath = public_path($canonicalPath);

        if (! $force && is_file($canonicalAbsolutePath)) {
            return self::metadataForPath($canonicalPath, $prompt);
        }

        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            return null;
        }

        $sourcePath = self::sourcePath($prompt, $preferredSource);

        if ($sourcePath === null) {
            return null;
        }

        $sourceAbsolutePath = public_path($sourcePath);
        $info = getimagesize($sourceAbsolutePath);

        if (! is_array($info) || ($info[0] ?? 0) < 1 || ($info[1] ?? 0) < 1) {
            return null;
        }

        $source = self::sourceImage($sourceAbsolutePath, (string) ($info['mime'] ?? ''));

        if (! $source) {
            return null;
        }

        $directory = dirname($canonicalAbsolutePath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            imagedestroy($source);
            return null;
        }

        $sourceWidth = (int) $info[0];
        $sourceHeight = (int) $info[1];
        $webpSaved = self::saveResized(
            $source,
            $sourceWidth,
            $sourceHeight,
            $sourceWidth,
            $canonicalAbsolutePath,
            'webp'
        );

        if (! $webpSaved) {
            imagedestroy($source);
            return null;
        }

        foreach (self::RESPONSIVE_WIDTHS as $width) {
            if ($width >= $sourceWidth) {
                continue;
            }

            self::saveResized(
                $source,
                $sourceWidth,
                $sourceHeight,
                $width,
                $directory . '/' . $slug . '-' . $width . 'w.webp',
                'webp'
            );
        }

        if (function_exists('imageavif')) {
            self::saveResized(
                $source,
                $sourceWidth,
                $sourceHeight,
                $sourceWidth,
                $directory . '/' . $slug . '.avif',
                'avif'
            );

            foreach (self::RESPONSIVE_WIDTHS as $width) {
                if ($width >= $sourceWidth) {
                    continue;
                }

                self::saveResized(
                    $source,
                    $sourceWidth,
                    $sourceHeight,
                    $width,
                    $directory . '/' . $slug . '-' . $width . 'w.avif',
                    'avif'
                );
            }
        }

        imagedestroy($source);

        return self::metadataForPath($canonicalPath, $prompt);
    }

    public static function plannedPath(array $prompt): string
    {
        return self::OUTPUT_DIRECTORY . '/' . self::imageSlug($prompt) . '.webp';
    }

    public static function altText(array $prompt): string
    {
        $title = preg_replace('/\s+/u', ' ', strip_tags(trim((string) ($prompt['title'] ?? '')))) ?? '';

        if ($title !== '') {
            return rtrim($title, " .\t\n\r\0\x0B");
        }

        $category = trim((string) ($prompt['category'] ?? ''));

        return $category !== ''
            ? ucfirst($category) . ' AI-generated image'
            : 'AI-generated image preview';
    }

    private static function metadataForPath(string $path, array $prompt): ?array
    {
        $absolutePath = public_path($path);
        $info = is_file($absolutePath) ? getimagesize($absolutePath) : false;

        if (! is_array($info)) {
            return null;
        }

        $alt = self::altText($prompt);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $webpSrcset = $extension === 'webp' ? self::srcset($path, 'webp') : null;
        $avifSrcset = $extension === 'webp' ? self::srcset($path, 'avif') : null;

        $versionedUrl = self::versionedAsset($path);

        return [
            'path' => $path,
            'src' => $versionedUrl,
            'url' => app_url($versionedUrl),
            'full_src' => $versionedUrl,
            'width' => (int) $info[0],
            'height' => (int) $info[1],
            'type' => (string) ($info['mime'] ?? ''),
            'alt' => $alt,
            'caption' => $alt . ' — full-size AI image preview.',
            'webp_srcset' => $webpSrcset,
            'avif_srcset' => $avifSrcset,
        ];
    }

    private static function srcset(string $canonicalPath, string $extension): ?string
    {
        $directory = dirname($canonicalPath);
        $stem = pathinfo($canonicalPath, PATHINFO_FILENAME);
        $canonicalForType = $directory . '/' . $stem . '.' . $extension;
        $candidates = [];

        foreach (array_merge([$canonicalForType], self::variantPaths($directory, $stem, $extension)) as $path) {
            $absolutePath = public_path($path);
            $info = is_file($absolutePath) ? getimagesize($absolutePath) : false;

            if (! is_array($info) || ($info[0] ?? 0) < 1) {
                continue;
            }

            $candidates[(int) $info[0]] = self::versionedAsset($path) . ' ' . (int) $info[0] . 'w';
        }

        if ($candidates === []) {
            return null;
        }

        ksort($candidates, SORT_NUMERIC);

        return implode(', ', $candidates);
    }

    private static function variantPaths(string $directory, string $stem, string $extension): array
    {
        $pattern = public_path($directory . '/' . $stem . '-*w.' . $extension);
        $paths = [];

        foreach (glob($pattern) ?: [] as $absolutePath) {
            $filename = basename($absolutePath);

            if (preg_match('/^' . preg_quote($stem, '/') . '-\d+w\.' . preg_quote($extension, '/') . '$/', $filename) === 1) {
                $paths[] = $directory . '/' . $filename;
            }
        }

        return $paths;
    }

    private static function sourcePath(array $prompt, ?string $preferredSource): ?string
    {
        foreach ([$preferredSource, $prompt['reference_image_path'] ?? null, $prompt['thumbnail_path'] ?? null] as $candidate) {
            $path = self::localRelativePath((string) $candidate);

            if ($path !== null && is_file(public_path($path))) {
                return $path;
            }
        }

        return null;
    }

    private static function versionedAsset(string $path): string
    {
        $modifiedAt = filemtime(public_path($path));

        return asset($path) . ($modifiedAt ? '?v=' . $modifiedAt : '');
    }

    private static function localRelativePath(string $path): ?string
    {
        $path = trim($path);

        if ($path === '' || preg_match('#^https?://#i', $path) === 1) {
            return null;
        }

        $path = ltrim($path, '/');

        if (str_contains($path, '../') || ! str_starts_with($path, 'storage/')) {
            return null;
        }

        return $path;
    }

    private static function imageSlug(array $prompt): string
    {
        $source = trim((string) ($prompt['source_slug'] ?? ''));

        if ($source === '') {
            $source = trim((string) ($prompt['title'] ?? ''));
        }

        $slug = Prompt::slugify($source);
        $slug = trim(substr($slug, 0, 120), '-');

        if ($slug !== '') {
            return $slug;
        }

        return 'prompt-image-' . max(1, (int) ($prompt['id'] ?? 0));
    }

    private static function sourceImage(string $path, string $mime): mixed
    {
        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
            'image/avif' => function_exists('imagecreatefromavif') ? imagecreatefromavif($path) : false,
            default => false,
        };
    }

    private static function saveResized(
        mixed $source,
        int $sourceWidth,
        int $sourceHeight,
        int $targetWidth,
        string $targetPath,
        string $format
    ): bool {
        $targetHeight = max(1, (int) round($sourceHeight * ($targetWidth / $sourceWidth)));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        if (! $target) {
            return false;
        }

        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefill($target, 0, 0, $transparent);
        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        $temporaryPath = $targetPath . '.tmp-' . bin2hex(random_bytes(6));
        $saved = $format === 'avif'
            ? imageavif($target, $temporaryPath, self::AVIF_QUALITY)
            : imagewebp($target, $temporaryPath, self::WEBP_QUALITY);
        imagedestroy($target);

        if (! $saved) {
            @unlink($temporaryPath);
            return false;
        }

        chmod($temporaryPath, 0644);

        if (! rename($temporaryPath, $targetPath)) {
            @unlink($temporaryPath);
            return false;
        }

        return true;
    }
}
