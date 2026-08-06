<?php

declare(strict_types=1);

namespace App\Services;

final class ImageService
{
    public static function thumbnail(string $relativePath, int $targetWidth = 640, int $targetHeight = 420): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $sourcePath = public_path($relativePath);

        if (! is_file($sourcePath)) {
            return null;
        }

        $info = getimagesize($sourcePath);

        if (! $info) {
            return null;
        }

        [$width, $height] = $info;
        $mime = $info['mime'] ?? '';
        $source = self::sourceImage($sourcePath, $mime);

        if (! $source) {
            return null;
        }

        $scale = max($targetWidth / $width, $targetHeight / $height);
        $resizeWidth = (int) ceil($width * $scale);
        $resizeHeight = (int) ceil($height * $scale);
        $offsetX = (int) floor(($targetWidth - $resizeWidth) / 2);
        $offsetY = (int) floor(($targetHeight - $resizeHeight) / 2);

        $thumb = imagecreatetruecolor($targetWidth, $targetHeight);
        imagefill($thumb, 0, 0, imagecolorallocate($thumb, 250, 250, 249));
        imagecopyresampled($thumb, $source, $offsetX, $offsetY, 0, 0, $resizeWidth, $resizeHeight, $width, $height);

        $folder = 'storage/prompts/thumbs/' . date('Y/m');
        $absoluteFolder = public_path($folder);

        if (! is_dir($absoluteFolder) && ! mkdir($absoluteFolder, 0755, true) && ! is_dir($absoluteFolder)) {
            imagedestroy($source);
            imagedestroy($thumb);
            return null;
        }

        $path = $folder . '/' . bin2hex(random_bytes(18)) . '.jpg';
        $saved = imagejpeg($thumb, public_path($path), 82);

        imagedestroy($source);
        imagedestroy($thumb);

        return $saved ? $path : null;
    }

    private static function sourceImage(string $path, string $mime): mixed
    {
        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
            default => false,
        };
    }
}
