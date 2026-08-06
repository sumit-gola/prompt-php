<?php

declare(strict_types=1);

use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\PromptController as AdminPromptController;
use App\Controllers\AuthController;
use App\Controllers\PromptController;
use App\Controllers\PublicController;

$router->get('/', [PublicController::class, 'home']);
$router->get('/about', [PublicController::class, 'about']);
$router->get('/contact', [PublicController::class, 'contact']);
$router->get('/privacy-policy', [PublicController::class, 'privacy']);
$router->get('/terms', [PublicController::class, 'terms']);
$router->get('/robots.txt', [PublicController::class, 'robots']);
$router->get('/sitemap.xml', [PublicController::class, 'sitemap']);
$router->get('/sitemaps/pages.xml', [PublicController::class, 'sitemapPages']);
$router->get('/sitemaps/prompts-{page}.xml', [PublicController::class, 'sitemapPrompts']);
$router->get('/ads.txt', [PublicController::class, 'ads']);

$router->get('/prompts', [PromptController::class, 'index']);
$router->post('/prompts/{id}/copy', [PromptController::class, 'copy'], ['csrf']);
$router->get('/prompts/category/{category}', [PromptController::class, 'category']);
$router->get('/prompts/{identifier}', [PromptController::class, 'show']);

$router->get('/register', [AuthController::class, 'showRegister'], ['guest']);
$router->post('/register', [AuthController::class, 'register'], ['guest', 'csrf']);
$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest', 'csrf']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth', 'csrf']);

$router->get('/admin', [DashboardController::class, 'index'], ['admin']);
$router->get('/admin/prompts', [AdminPromptController::class, 'index'], ['admin']);
$router->get('/admin/prompts/create', [AdminPromptController::class, 'create'], ['admin']);
$router->post('/admin/prompts', [AdminPromptController::class, 'store'], ['admin', 'csrf']);
$router->post('/admin/prompts/bulk', [AdminPromptController::class, 'bulk'], ['admin', 'csrf']);
$router->get('/admin/prompts/{id}/edit', [AdminPromptController::class, 'edit'], ['admin']);
$router->post('/admin/prompts/{id}', [AdminPromptController::class, 'update'], ['admin', 'csrf']);
$router->post('/admin/prompts/{id}/delete', [AdminPromptController::class, 'destroy'], ['admin', 'csrf']);
$router->post('/admin/prompts/{id}/regenerate', [AdminPromptController::class, 'regenerate'], ['admin', 'csrf']);
