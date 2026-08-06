<?php

declare(strict_types=1);

$router = require dirname(__DIR__) . '/bootstrap/app.php';
require base_path('routes/web.php');

$routes = $router->routes();
$failures = [];

$findRoute = static function (string $method, string $path) use ($routes): ?array {
    foreach ($routes as $route) {
        if ($route['method'] === $method && $route['path'] === $path) {
            return $route;
        }
    }

    return null;
};

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$assertSearchPlaceholders = static function (string $label, array $whereResult) use ($assert): void {
    [$where, $params] = $whereResult;
    preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*)/', $where, $matches);
    $placeholders = $matches[1];

    foreach (array_count_values($placeholders) as $placeholder => $count) {
        $assert($count === 1, "{$label} reuses named placeholder :{$placeholder}");
    }

    foreach ($placeholders as $placeholder) {
        $assert(array_key_exists($placeholder, $params), "{$label} missing bound value for :{$placeholder}");
    }
};

foreach ([
    ['GET', '/'],
    ['GET', '/prompts'],
    ['GET', '/prompts/{identifier}'],
    ['POST', '/prompts/{id}/copy'],
    ['GET', '/about'],
    ['GET', '/contact'],
    ['GET', '/privacy-policy'],
    ['GET', '/terms'],
    ['GET', '/robots.txt'],
    ['GET', '/sitemap.xml'],
    ['GET', '/sitemaps/pages.xml'],
    ['GET', '/sitemaps/prompts-{page}.xml'],
    ['GET', '/ads.txt'],
    ['GET', '/prompts/category/{category}'],
] as [$method, $path]) {
    $assert($findRoute($method, $path) !== null, "Missing public route {$method} {$path}");
}

foreach ([
    ['GET', '/admin'],
    ['GET', '/admin/prompts'],
    ['GET', '/admin/prompts/create'],
    ['POST', '/admin/prompts'],
    ['POST', '/admin/prompts/bulk'],
    ['GET', '/admin/prompts/{id}/edit'],
    ['POST', '/admin/prompts/{id}'],
    ['POST', '/admin/prompts/{id}/delete'],
    ['POST', '/admin/prompts/{id}/regenerate'],
] as [$method, $path]) {
    $route = $findRoute($method, $path);
    $assert($route !== null, "Missing admin route {$method} {$path}");
    $assert($route === null || in_array('admin', $route['middleware'], true), "Admin route lacks admin middleware: {$method} {$path}");
}

foreach ($routes as $route) {
    if ($route['method'] === 'POST') {
        $assert(in_array('csrf', $route['middleware'], true), "POST route lacks CSRF middleware: {$route['path']}");
    }
}

$copyRoute = $findRoute('POST', '/prompts/{id}/copy');
$assert($copyRoute !== null && ! in_array('admin', $copyRoute['middleware'], true), 'Copy endpoint should be public but CSRF protected.');

$routePaths = array_column($routes, 'path');
$categoryRoutePosition = array_search('/prompts/category/{category}', $routePaths, true);
$promptRoutePosition = array_search('/prompts/{identifier}', $routePaths, true);
$assert(
    is_int($categoryRoutePosition) && is_int($promptRoutePosition) && $categoryRoutePosition < $promptRoutePosition,
    'Category route must be registered before the generic prompt route.'
);

$promptReflection = new ReflectionClass(\App\Models\Prompt::class);
$publicWhere = $promptReflection->getMethod('publicWhere');
$adminWhere = $promptReflection->getMethod('adminWhere');
$publicWhere->setAccessible(true);
$adminWhere->setAccessible(true);

$assertSearchPlaceholders('Public search', $publicWhere->invoke(null, [
    'q' => 'man walking',
    'category' => 'portrait',
]));
$assertSearchPlaceholders('Admin search', $adminWhere->invoke(null, [
    'q' => 'man walking',
    'status' => 'completed',
    'category' => 'portrait',
    'generation_mode' => 'imported',
    'source' => 'example',
    'date_from' => '2026-08-01',
    'date_to' => '2026-08-06',
]));

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }
    exit(1);
}

echo "Smoke checks passed: " . count($routes) . " routes registered with expected permissions.\n";
