<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Response;
use App\Services\SeoService;

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicRoot = realpath(__DIR__) ?: __DIR__;
$staticPath = realpath(__DIR__ . $uri);

if (PHP_SAPI === 'cli-server' && $uri !== '/' && $staticPath && str_starts_with($staticPath, $publicRoot) && is_file($staticPath)) {
    return false;
}

$appBootstrap = dirname(__DIR__) . '/bootstrap/app.php';
$cPanelBootstrap = dirname(__DIR__) . '/prompt-cms/bootstrap/app.php';

if (! is_file($appBootstrap) && is_file($cPanelBootstrap)) {
    $appBootstrap = $cPanelBootstrap;
}

$router = require $appBootstrap;
require base_path('routes/web.php');

header_remove('X-Powered-By');
$request = Request::capture();

try {
    if (strtolower((string) env('APP_ENV', 'local')) === 'production') {
        $appUrl = parse_url((string) env('APP_URL', ''));

        if (($appUrl['scheme'] ?? null) !== 'https' || empty($appUrl['host'])) {
            throw new RuntimeException('Production APP_URL must be an absolute HTTPS URL.');
        }

        $canonicalRedirect = SeoService::canonicalRedirectUrl($request);

        if ($canonicalRedirect !== null) {
            Response::redirect($canonicalRedirect, 301)->send($request);
            return;
        }
    }

    $router->dispatch($request)->send($request);
} catch (Throwable $exception) {
    error_log((string) $exception);

    $debug = filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN);
    $message = $debug
        ? '<pre>' . e((string) $exception) . '</pre>'
        : '<h1>Something went wrong</h1><p>The application could not complete this request.</p>';

    Response::html($message, 500, ['X-Robots-Tag' => 'noindex, nofollow'])->send($request);
}
