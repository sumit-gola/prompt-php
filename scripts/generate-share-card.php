<?php

declare(strict_types=1);

if (! extension_loaded('gd')) {
    fwrite(STDERR, "GD extension is required.\n");
    exit(1);
}

$root = dirname(__DIR__);
$target = $root . '/public/assets/img/share-card-library.png';

if (! is_dir(dirname($target))) {
    mkdir(dirname($target), 0755, true);
}

$width = 1200;
$height = 630;
$image = imagecreatetruecolor($width, $height);
imagealphablending($image, true);
imagesavealpha($image, true);

$fontRegular = fontPath([
    '/System/Library/Fonts/SFNS.ttf',
    '/System/Library/Fonts/HelveticaNeue.ttc',
    '/Library/Fonts/Arial.ttf',
]);
$fontBold = fontPath([
    '/System/Library/Fonts/SFNS.ttf',
    '/System/Library/Fonts/HelveticaNeue.ttc',
    '/Library/Fonts/Arial Bold.ttf',
]);

$ink = color($image, '#101828');
$muted = color($image, '#64748b');
$softMuted = color($image, '#94a3b8');
$blue = color($image, '#0e7490');
$cyan = color($image, '#0891b2');
$teal = color($image, '#0f766e');
$green = color($image, '#22c55e');
$white = color($image, '#ffffff');
$panel = color($image, '#f8fbff');
$line = color($image, '#d5e4f3');
$grid = colorAlpha($image, '#0e7490', 112);

verticalGradient($image, 0, 0, $width, $height, '#f8fbff', '#eef7ff');
drawGrid($image, 28, $grid);

// Top application bar.
filledRect($image, 0, 0, $width, 58, $white);
line($image, 0, 57, $width, 57, $line);
roundedRect($image, 24, 14, 34, 34, 10, color($image, '#eaf7ff'));
roundedBorder($image, 24, 14, 34, 34, 10, color($image, '#bde2f5'), 1);
drawText($image, 'PL', 35, 38, 13, $fontBold, $blue);
drawText($image, 'Prompt Library', 72, 40, 16, $fontBold, $ink);

roundedRect($image, 278, 11, 410, 38, 9, $white);
roundedBorder($image, 278, 11, 410, 38, 9, $line, 1);
roundedRect($image, 292, 20, 30, 20, 6, color($image, '#e7f6ff'));
drawText($image, 'AI', 301, 35, 11, $fontBold, $cyan);
drawText($image, 'Search prompts', 340, 37, 15, $fontRegular, $softMuted);
roundedRect($image, 696, 11, 78, 38, 9, $cyan);
drawText($image, 'Search', 714, 37, 15, $fontBold, $white);
drawText($image, 'Library', 884, 39, 15, $fontBold, color($image, '#475569'));
drawText($image, 'About', 958, 39, 15, $fontBold, color($image, '#475569'));
drawText($image, 'Contact', 1022, 39, 15, $fontBold, color($image, '#475569'));
drawText($image, 'Sign in', 1100, 39, 15, $fontBold, color($image, '#475569'));

// Command-center hero area.
drawText($image, 'PROMPT  COMMAND  CENTER', 24, 98, 12, $fontBold, $blue);
drawText($image, 'Browse 1070 AI prompts.', 24, 152, 46, $fontBold, $ink);

drawPill($image, 24, 176, '1070', 'COMPLETED', $fontBold, $ink, $muted);
drawPill($image, 142, 176, '6', 'CATEGORIES', $fontBold, $ink, $muted);
drawPill($image, 242, 176, '10', 'COPIES', $fontBold, $ink, $muted);

$chipX = 24;
foreach (['PORTRAIT', 'PRODUCT', 'FASHION', 'LIFESTYLE', 'ART', 'OTHER'] as $chip) {
    $chipW = max(58, textWidth($chip, 10, $fontBold) + 28);
    roundedRect($image, $chipX, 218, $chipW, 28, 14, color($image, '#f8fbff'));
    roundedBorder($image, $chipX, 218, $chipW, 28, 14, $line, 1);
    drawText($image, $chip, $chipX + 14, 237, 10, $fontBold, color($image, '#607389'));
    $chipX += $chipW + 9;
}

roundedRect($image, 758, 148, 394, 78, 10, $white);
roundedBorder($image, 758, 148, 394, 78, 10, $line, 1);
drawText($image, 'Search the library', 772, 174, 14, $fontBold, color($image, '#334155'));
roundedRect($image, 772, 182, 290, 36, 8, $white);
roundedBorder($image, 772, 182, 290, 36, 8, $line, 1);
drawText($image, '>', 786, 207, 15, $fontBold, $teal);
drawText($image, 'Try portrait lighting, fashion...', 808, 207, 13, $fontRegular, $softMuted);
roundedRect($image, 1070, 182, 66, 36, 8, $cyan);
drawText($image, 'Search', 1084, 207, 14, $fontBold, $white);

drawText($image, 'LATEST COMPLETED', 24, 285, 12, $fontBold, $blue);
drawText($image, 'Newest in the library', 24, 318, 20, $fontBold, $ink);
roundedRect($image, 1075, 279, 77, 38, 8, $white);
roundedBorder($image, 1075, 279, 77, 38, 8, $line, 1);
drawText($image, 'View all', 1091, 304, 14, $fontBold, color($image, '#334155'));

$thumbnailPaths = thumbnailPaths($root);
$cards = [
    [
        'category' => 'PORTRAIT',
        'copied' => '0 COPIED',
        'id' => '#1074',
        'title' => 'Double Exposure Watercolor Portrait...',
        'excerpt' => 'A creative double-exposure style composite artwork featuring a young man with wavy dark hair.',
    ],
    [
        'category' => 'LIFESTYLE',
        'copied' => '0 COPIED',
        'id' => '#1073',
        'title' => 'Man Walking Barefoot on Beach at Golden Hour',
        'excerpt' => 'A young man with wavy dark hair and a full beard walking barefoot along the shoreline at sunset.',
    ],
    [
        'category' => 'LIFESTYLE',
        'copied' => '1 COPIED',
        'id' => '#1072',
        'title' => 'Golden Hour Portrait in Wheat Field',
        'excerpt' => 'A three-quarter length portrait of a young man in a golden wheat field at sunset.',
    ],
    [
        'category' => 'PORTRAIT',
        'copied' => '1 COPIED',
        'id' => '#1071',
        'title' => 'Man Leaning Against Pine Tree in...',
        'excerpt' => 'A young man with thick dark hair styled back, leaning against a tall pine tree trunk.',
    ],
];

$cardX = 24;
$cardY = 338;
$cardW = 267;
$cardH = 272;
$gap = 20;

foreach ($cards as $index => $card) {
    drawPromptCard(
        $image,
        $cardX + ($cardW + $gap) * $index,
        $cardY,
        $cardW,
        $cardH,
        $card,
        $thumbnailPaths[$index] ?? null,
        $fontRegular,
        $fontBold
    );
}

imagepng($image, $target, 8);
imagedestroy($image);

echo $target . "\n";

function thumbnailPaths(string $root): array
{
    $paths = glob($root . '/public/storage/prompts/thumbnails/*.{jpg,jpeg,png,webp,avif}', GLOB_BRACE) ?: [];
    sort($paths);

    return array_values($paths);
}

function drawPromptCard(GdImage $image, int $x, int $y, int $w, int $h, array $card, ?string $thumbnail, ?string $fontRegular, ?string $fontBold): void
{
    $ink = color($image, '#101828');
    $muted = color($image, '#64748b');
    $line = color($image, '#d5e4f3');
    $white = color($image, '#ffffff');
    $cyan = color($image, '#0891b2');
    $green = color($image, '#22c55e');

    shadow($image, $x, $y, $w, $h, 12);
    roundedRect($image, $x, $y, $w, $h, 10, $white);
    roundedBorder($image, $x, $y, $w, $h, 10, $line, 1);

    drawCover($image, $thumbnail, $x + 1, $y + 1, $w - 2, 116);
    line($image, $x, $y + 116, $x + $w, $y + 116, $line);

    drawMetaChip($image, $x + 14, $y + 132, (string) $card['category'], $fontBold);
    drawMetaChip($image, $x + 92, $y + 132, (string) $card['copied'], $fontBold);
    drawMetaChip($image, $x + $w - 58, $y + 132, (string) $card['id'], $fontBold);

    drawWrappedText($image, (string) $card['title'], $x + 14, $y + 172, $w - 28, 16, 2, $fontBold, $ink, 20);
    drawWrappedText($image, (string) $card['excerpt'], $x + 14, $y + 216, $w - 28, 13, 2, $fontRegular, $muted, 18);

    roundedRect($image, $x + 14, $y + $h - 42, 52, 30, 7, $white);
    roundedBorder($image, $x + 14, $y + $h - 42, 52, 30, 7, $line, 1);
    drawText($image, 'Open', $x + 25, $y + $h - 22, 12, $fontBold, color($image, '#334155'));

    roundedRect($image, $x + $w - 76, $y + $h - 42, 62, 30, 7, $cyan);
    imagefilledellipse($image, $x + $w - 64, $y + $h - 27, 5, 5, $green);
    drawText($image, 'Copy', $x + $w - 49, $y + $h - 22, 12, $fontBold, $white);
}

function drawCover(GdImage $image, ?string $path, int $x, int $y, int $w, int $h): void
{
    if ($path !== null && is_file($path)) {
        $source = loadImage($path);

        if ($source instanceof GdImage) {
            $srcW = imagesx($source);
            $srcH = imagesy($source);
            $srcRatio = $srcW / max(1, $srcH);
            $dstRatio = $w / max(1, $h);

            if ($srcRatio > $dstRatio) {
                $cropH = $srcH;
                $cropW = (int) round($srcH * $dstRatio);
                $cropX = (int) round(($srcW - $cropW) / 2);
                $cropY = 0;
            } else {
                $cropW = $srcW;
                $cropH = (int) round($srcW / $dstRatio);
                $cropX = 0;
                $cropY = (int) round(($srcH - $cropH) / 2);
            }

            imagecopyresampled($image, $source, $x, $y, $cropX, $cropY, $w, $h, $cropW, $cropH);
            imagedestroy($source);
            return;
        }
    }

    verticalGradient($image, $x, $y, $w, $h, '#dff3ff', '#eef2ff');
    drawText($image, 'AI', $x + (int) ($w / 2) - 16, $y + (int) ($h / 2) + 8, 22, null, color($image, '#0891b2'));
}

function loadImage(string $path): ?GdImage
{
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    return match ($extension) {
        'jpg', 'jpeg' => @imagecreatefromjpeg($path) ?: null,
        'png' => @imagecreatefrompng($path) ?: null,
        'webp' => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
        'avif' => function_exists('imagecreatefromavif') ? (@imagecreatefromavif($path) ?: null) : null,
        default => null,
    };
}

function drawPill(GdImage $image, int $x, int $y, string $value, string $label, ?string $fontBold, int $valueColor, int $labelColor): void
{
    $w = textWidth($value . ' ' . $label, 12, $fontBold) + 27;
    roundedRect($image, $x, $y, $w, 30, 15, color($image, '#f8fbff'));
    roundedBorder($image, $x, $y, $w, 30, 15, color($image, '#d5e4f3'), 1);
    drawText($image, $value, $x + 13, $y + 20, 13, $fontBold, $valueColor);
    drawText($image, $label, $x + 13 + textWidth($value, 13, $fontBold) + 9, $y + 20, 9, $fontBold, $labelColor);
}

function drawMetaChip(GdImage $image, int $x, int $y, string $label, ?string $fontBold): void
{
    $w = max(48, textWidth($label, 9, $fontBold) + 20);
    roundedRect($image, $x, $y, $w, 25, 13, color($image, '#f8fbff'));
    roundedBorder($image, $x, $y, $w, 25, 13, color($image, '#d5e4f3'), 1);
    drawText($image, $label, $x + 10, $y + 17, 9, $fontBold, color($image, '#607389'));
}

function drawWrappedText(GdImage $image, string $text, int $x, int $y, int $maxWidth, int $size, int $maxLines, ?string $font, int $color, int $lineHeight): void
{
    $words = preg_split('/\s+/', trim($text)) ?: [];
    $lines = [];
    $line = '';

    foreach ($words as $word) {
        $candidate = trim($line . ' ' . $word);

        if ($line !== '' && textWidth($candidate, $size, $font) > $maxWidth) {
            $lines[] = $line;
            $line = $word;
        } else {
            $line = $candidate;
        }
    }

    if ($line !== '') {
        $lines[] = $line;
    }

    $lines = array_slice($lines, 0, $maxLines);

    if (count($lines) === $maxLines && textWidth(end($lines), $size, $font) > $maxWidth - 12) {
        $lines[$maxLines - 1] = fitText($lines[$maxLines - 1], $maxWidth, $size, $font);
    }

    foreach ($lines as $index => $lineText) {
        drawText($image, $lineText, $x, $y + ($index * $lineHeight), $size, $font, $color);
    }
}

function fitText(string $text, int $maxWidth, int $size, ?string $font): string
{
    $text = rtrim($text, '.');

    while ($text !== '' && textWidth($text . '...', $size, $font) > $maxWidth) {
        $text = rtrim(substr($text, 0, -1));
    }

    return $text . '...';
}

function fontPath(array $paths): ?string
{
    foreach ($paths as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}

function textWidth(string $text, int $size, ?string $font): int
{
    if ($font !== null && function_exists('imagettfbbox')) {
        $box = imagettfbbox($size, 0, $font, $text);

        if ($box !== false) {
            return abs($box[2] - $box[0]);
        }
    }

    return strlen($text) * max(6, (int) round($size * .62));
}

function color(GdImage $image, string $hex): int
{
    [$r, $g, $b] = rgb($hex);

    return imagecolorallocate($image, $r, $g, $b);
}

function colorAlpha(GdImage $image, string $hex, int $alpha): int
{
    [$r, $g, $b] = rgb($hex);

    return imagecolorallocatealpha($image, $r, $g, $b, $alpha);
}

function rgb(string $hex): array
{
    $hex = ltrim($hex, '#');

    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ];
}

function drawText(GdImage $image, string $text, int $x, int $y, int $size, ?string $font, int $color): void
{
    if ($font !== null && function_exists('imagettftext')) {
        imagettftext($image, $size, 0, $x, $y, $color, $font, $text);
        return;
    }

    imagestring($image, min(5, max(1, (int) floor($size / 5))), $x, $y - $size, $text, $color);
}

function verticalGradient(GdImage $image, int $x, int $y, int $w, int $h, string $from, string $to): void
{
    [$r1, $g1, $b1] = rgb($from);
    [$r2, $g2, $b2] = rgb($to);

    for ($i = 0; $i < $h; $i++) {
        $ratio = $i / max(1, $h - 1);
        $color = imagecolorallocate(
            $image,
            (int) round($r1 + ($r2 - $r1) * $ratio),
            (int) round($g1 + ($g2 - $g1) * $ratio),
            (int) round($b1 + ($b2 - $b1) * $ratio)
        );
        imageline($image, $x, $y + $i, $x + $w, $y + $i, $color);
    }
}

function roundedRect(GdImage $image, int $x, int $y, int $w, int $h, int $r, int $color): void
{
    if ($r <= 0) {
        imagefilledrectangle($image, $x, $y, $x + $w, $y + $h, $color);
        return;
    }

    imagefilledrectangle($image, $x + $r, $y, $x + $w - $r, $y + $h, $color);
    imagefilledrectangle($image, $x, $y + $r, $x + $w, $y + $h - $r, $color);
    imagefilledellipse($image, $x + $r, $y + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($image, $x + $w - $r, $y + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($image, $x + $r, $y + $h - $r, $r * 2, $r * 2, $color);
    imagefilledellipse($image, $x + $w - $r, $y + $h - $r, $r * 2, $r * 2, $color);
}

function roundedBorder(GdImage $image, int $x, int $y, int $w, int $h, int $r, int $color, int $thickness): void
{
    for ($i = 0; $i < $thickness; $i++) {
        imagerectangle($image, $x + $r, $y + $i, $x + $w - $r, $y + $h - $i, $color);
        imagerectangle($image, $x + $i, $y + $r, $x + $w - $i, $y + $h - $r, $color);
        imagearc($image, $x + $r, $y + $r, $r * 2, $r * 2, 180, 270, $color);
        imagearc($image, $x + $w - $r, $y + $r, $r * 2, $r * 2, 270, 360, $color);
        imagearc($image, $x + $r, $y + $h - $r, $r * 2, $r * 2, 90, 180, $color);
        imagearc($image, $x + $w - $r, $y + $h - $r, $r * 2, $r * 2, 0, 90, $color);
    }
}

function drawGrid(GdImage $image, int $gap, int $color): void
{
    $w = imagesx($image);
    $h = imagesy($image);

    for ($x = 0; $x <= $w; $x += $gap) {
        imageline($image, $x, 58, $x, $h, $color);
    }

    for ($y = 58; $y <= $h; $y += $gap) {
        imageline($image, 0, $y, $w, $y, $color);
    }
}

function filledRect(GdImage $image, int $x, int $y, int $w, int $h, int $color): void
{
    imagefilledrectangle($image, $x, $y, $x + $w, $y + $h, $color);
}

function line(GdImage $image, int $x1, int $y1, int $x2, int $y2, int $color): void
{
    imageline($image, $x1, $y1, $x2, $y2, $color);
}

function shadow(GdImage $image, int $x, int $y, int $w, int $h, int $radius): void
{
    for ($i = 5; $i >= 1; $i--) {
        roundedRect($image, $x + $i, $y + $i, $w, $h, $radius, colorAlpha($image, '#64748b', 124 - ($i * 12)));
    }
}
