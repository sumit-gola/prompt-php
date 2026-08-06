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
    ['GET', '/ads.txt'],
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

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }
    exit(1);
}

echo "Smoke checks passed: " . count($routes) . " routes registered with expected permissions.\n";

