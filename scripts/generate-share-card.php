<?php

declare(strict_types=1);

if (! extension_loaded('gd')) {
    fwrite(STDERR, "GD extension is required.\n");
    exit(1);
}

$root = dirname(__DIR__);
$target = $root . '/public/assets/img/share-card.png';

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

$ink = color($image, '#111827');
$muted = color($image, '#64748b');
$blue = color($image, '#2563eb');
$cyan = color($image, '#0891b2');
$teal = color($image, '#0f766e');
$violet = color($image, '#7c3aed');
$white = color($image, '#ffffff');
$panel = color($image, '#f8fbff');
$line = color($image, '#d8e5f2');
$soft = color($image, '#eef7ff');

verticalGradient($image, 0, 0, $width, $height, '#f8fbff', '#eef6ff');
drawGrid($image, 30, colorAlpha($image, '#0891b2', 18));

roundedRect($image, 40, 36, 1120, 558, 28, $white);
roundedBorder($image, 40, 36, 1120, 558, 28, color($image, '#cfe2f3'), 2);
horizontalGradient($image, 42, 38, 1116, 250, '#ffffff', '#f2f7ff');

roundedRect($image, 96, 104, 78, 78, 18, color($image, '#eef6ff'));
roundedBorder($image, 96, 104, 78, 78, 18, color($image, '#cae6f6'), 2);
drawText($image, 'MP', 120, 154, 25, $fontBold, $blue);

drawText($image, 'MyPromptArt', 194, 150, 45, $fontBold, $ink);
drawText($image, 'AI Prompt Library', 96, 250, 42, $fontBold, $blue);
drawText($image, 'That Creators Copy', 96, 300, 42, $fontBold, $violet);
drawText($image, 'Search, open, and copy completed AI image prompts.', 98, 340, 18, $fontRegular, $muted);

drawAccentRule($image, 98, 366, 128, $cyan, $violet);

$stats = [
    ['1000+', 'Completed prompts'],
    ['6', 'Creative categories'],
    ['Copy', 'Ready-to-use text'],
];

$x = 98;
foreach ($stats as [$value, $label]) {
    roundedRect($image, $x, 402, 160, 72, 14, $panel);
    roundedBorder($image, $x, 402, 160, 72, 14, $line, 1);
    drawText($image, $value, $x + 18, 434, 20, $fontBold, $ink);
    drawText($image, $label, $x + 18, 460, 12, $fontRegular, $muted);
    $x += 174;
}

$chips = ['Portrait', 'Cinematic', 'Fashion', 'Product'];
$x = 98;
foreach ($chips as $chip) {
    roundedRect($image, $x, 482, 104, 32, 16, $soft);
    roundedBorder($image, $x, 482, 104, 32, 16, color($image, '#cfe4f5'), 1);
    drawText($image, $chip, $x + 18, 504, 12, $fontBold, $teal);
    $x += 118;
}

$cards = [
    ['Portrait Glow', 'Warm light, natural skin, soft lens depth', '#e0f2fe', '#dbeafe'],
    ['Face-Lock Edit', 'Keep identity, improve detail and tone', '#f5f3ff', '#ede9fe'],
    ['Studio Product', 'Clean surface, controlled reflections', '#ecfeff', '#ccfbf1'],
    ['Viral Aesthetic', 'Sharp color, cinematic contrast', '#fdf2f8', '#fae8ff'],
];

$positions = [[650, 76], [910, 76], [650, 320], [910, 320]];
foreach ($cards as $index => [$title, $copy, $from, $to]) {
    [$cx, $cy] = $positions[$index];
    roundedRect($image, $cx, $cy, 220, 196, 18, $white);
    roundedBorder($image, $cx, $cy, 220, 196, 18, $line, 1);
    drawText($image, $title, $cx + 20, $cy + 34, 16, $fontBold, $ink);
    drawText($image, $copy, $cx + 20, $cy + 58, 10, $fontRegular, $muted);

    roundedRect($image, $cx + 18, $cy + 80, 82, 78, 13, color($image, $from));
    roundedRect($image, $cx + 120, $cy + 80, 82, 78, 13, color($image, $to));
    drawPreviewFace($image, $cx + 18, $cy + 80, $from, $index);
    drawPreviewFace($image, $cx + 120, $cy + 80, $to, $index + 1);
    roundedRect($image, $cx + 94, $cy + 104, 34, 34, 17, color($image, '#334155'));
    drawText($image, '>', $cx + 106, $cy + 129, 16, $fontBold, $white);

    roundedRect($image, $cx + 18, $cy + 166, 62, 20, 10, color($image, '#eef6ff'));
    drawText($image, 'PROMPT', $cx + 29, $cy + 181, 9, $fontBold, $blue);
    roundedBorder($image, $cx + 166, $cy + 162, 32, 32, 8, color($image, '#c4d8ee'), 2);
}

roundedRect($image, 40, 526, 1120, 68, 0, color($image, '#0f513f'));
drawText($image, 'MyPromptArt - AI prompt library for creators', 92, 568, 24, $fontBold, $white);
drawText($image, 'mypromptart.com', 890, 568, 22, $fontBold, color($image, '#d1fae5'));

imagepng($image, $target, 8);
imagedestroy($image);

echo $target . "\n";

function fontPath(array $paths): ?string
{
    foreach ($paths as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
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

function horizontalGradient(GdImage $image, int $x, int $y, int $w, int $h, string $from, string $to): void
{
    [$r1, $g1, $b1] = rgb($from);
    [$r2, $g2, $b2] = rgb($to);

    for ($i = 0; $i < $w; $i++) {
        $ratio = $i / max(1, $w - 1);
        $color = imagecolorallocate(
            $image,
            (int) round($r1 + ($r2 - $r1) * $ratio),
            (int) round($g1 + ($g2 - $g1) * $ratio),
            (int) round($b1 + ($b2 - $b1) * $ratio)
        );
        imageline($image, $x + $i, $y, $x + $i, $y + $h, $color);
    }
}

function roundedRect(GdImage $image, int $x, int $y, int $w, int $h, int $r, int $color): void
{
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
        imageline($image, $x, 0, $x, $h, $color);
    }

    for ($y = 0; $y <= $h; $y += $gap) {
        imageline($image, 0, $y, $w, $y, $color);
    }
}

function drawAccentRule(GdImage $image, int $x, int $y, int $w, int $from, int $to): void
{
    imagefilledrectangle($image, $x, $y, $x + (int) ($w * .55), $y + 3, $from);
    imagefilledrectangle($image, $x + (int) ($w * .55), $y, $x + $w, $y + 3, $to);
}

function drawPreviewFace(GdImage $image, int $x, int $y, string $bg, int $seed): void
{
    roundedRect($image, $x, $y, 82, 78, 13, color($image, $bg));
    imagefilledellipse($image, $x + 42, $y + 28, 26, 30, color($image, '#d8a48f'));
    imagefilledarc($image, $x + 42, $y + 22, 34, 24, 190, 350, color($image, $seed % 2 === 0 ? '#1f2937' : '#6b3f2d'), IMG_ARC_PIE);
    imagefilledrectangle($image, $x + 26, $y + 46, $x + 58, $y + 72, color($image, $seed % 2 === 0 ? '#334155' : '#0f766e'));
    imageline($image, $x + 20, $y + 72, $x + 64, $y + 72, colorAlpha($image, '#111827', 70));
}
