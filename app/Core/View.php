<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        $templatePath = base_path('resources/views/' . trim($template, '/') . '.php');

        if (! is_file($templatePath)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $templatePath;
        $content = ob_get_clean();

        if ($layout === null) {
            return (string) $content;
        }

        $layoutPath = base_path('resources/views/' . trim($layout, '/') . '.php');

        if (! is_file($layoutPath)) {
            throw new \RuntimeException('Layout not found: ' . $layout);
        }

        ob_start();
        require $layoutPath;

        return (string) ob_get_clean();
    }
}

