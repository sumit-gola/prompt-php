<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Response;

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicRoot = realpath(__DIR__) ?: __DIR__;
$staticPath = realpath(__DIR__ . $uri);

if (PHP_SAPI === 'cli-server' && $uri !== '/' && $staticPath && str_starts_with($staticPath, $publicRoot) && is_file($staticPath)) {
    return false;
}

$router = require dirname(__DIR__) . '/bootstrap/app.php';
require base_path('routes/web.php');

header_remove('X-Powered-By');
$request = Request::capture();

try {
    $router->dispatch($request)->send($request);
} catch (Throwable $exception) {
    error_log((string) $exception);

    $debug = filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN);
    $message = $debug
        ? '<pre>' . e((string) $exception) . '</pre>'
        : '<h1>Something went wrong</h1><p>The application could not complete this request.</p>';

    Response::html($message, 500)->send($request);
}
