<?php

declare(strict_types=1);

namespace App\Services;

final class StorageService
{
    private const IMAGE_MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public static function normalizeFiles(array $files): array
    {
        if (! isset($files['name']) || ! is_array($files['name'])) {
            return ($files !== [] && isset($files['tmp_name'])) ? [$files] : [];
        }

        $normalized = [];

        foreach ($files['name'] as $index => $name) {
            $normalized[] = [
                'name' => $name,
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];
        }

        return $normalized;
    }

    public static function storeImage(array $file, string $directory = 'prompts'): string
    {
        self::assertValidImage($file);

        $mime = self::mimeType((string) $file['tmp_name']);
        $extension = self::IMAGE_MIME_EXTENSIONS[$mime];
        $folder = 'storage/' . trim($directory, '/') . '/' . date('Y/m');
        $absoluteFolder = public_path($folder);

        if (! is_dir($absoluteFolder) && ! mkdir($absoluteFolder, 0755, true) && ! is_dir($absoluteFolder)) {
            throw new \RuntimeException('Could not create upload directory.');
        }

        $filename = bin2hex(random_bytes(18)) . '.' . $extension;
        $absolutePath = $absoluteFolder . '/' . $filename;

        $moved = PHP_SAPI === 'cli'
            ? rename((string) $file['tmp_name'], $absolutePath)
            : move_uploaded_file((string) $file['tmp_name'], $absolutePath);

        if (! $moved) {
            throw new \RuntimeException('The uploaded file could not be stored.');
        }

        chmod($absolutePath, 0644);

        return $folder . '/' . $filename;
    }

    public static function deletePublicPath(?string $path): void
    {
        if (! $path) {
            return;
        }

        $absolute = public_path($path);
        $storageRoot = realpath(public_path('storage'));
        $target = realpath($absolute);

        if (! $storageRoot || ! $target || ! str_starts_with($target, $storageRoot)) {
            return;
        }

        if (is_file($target)) {
            unlink($target);
        }
    }

    public static function assertReferenceImages(array $files): array
    {
        $normalized = array_values(array_filter(
            self::normalizeFiles($files),
            static fn (array $file): bool => (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
        ));

        if (count($normalized) < 1 || count($normalized) > 10) {
            throw new \InvalidArgumentException('Upload between 1 and 10 reference images.');
        }

        foreach ($normalized as $file) {
            self::assertValidImage($file);
        }

        return $normalized;
    }

    public static function assertValidImage(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Upload a valid image file.');
        }

        if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('Images must be 5MB or smaller.');
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');

        if ($tmpPath === '' || ! is_file($tmpPath)) {
            throw new \InvalidArgumentException('Upload a valid image file.');
        }

        $mime = self::mimeType($tmpPath);

        if (! array_key_exists($mime, self::IMAGE_MIME_EXTENSIONS)) {
            throw new \InvalidArgumentException('Only JPG, PNG, and WebP images are allowed.');
        }
    }

    private static function mimeType(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path);

        return is_string($mime) ? $mime : '';
    }
}

