<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler, array $middleware = []): self
    {
        return $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = ['csrf']): self
    {
        return $this->add('POST', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, array|callable $handler, array $middleware = []): self
    {
        $path = $path === '/' ? '/' : '/' . trim($path, '/');
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = compact('method', 'path', 'pattern', 'handler', 'middleware');

        return $this;
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            if (! preg_match($route['pattern'], $request->path(), $matches)) {
                continue;
            }

            $middlewareResponse = $this->runMiddleware($route['middleware'], $request);

            if ($middlewareResponse instanceof Response) {
                return $middlewareResponse;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            return $this->call($route['handler'], $request, $params);
        }

        return Response::html(View::render('public/404', ['title' => 'Page not found'], 'layouts/public'), 404);
    }

    public function routes(): array
    {
        return $this->routes;
    }

    private function runMiddleware(array $middleware, Request $request): ?Response
    {
        if (in_array('csrf', $middleware, true) && ! Csrf::verify((string) $request->input('_token', ''))) {
            if ($request->wantsJson()) {
                return Response::json(['ok' => false, 'message' => 'Invalid CSRF token.'], 419);
            }

            Session::setFlash('error', 'Your session expired. Please try again.');
            return Response::redirect((string) ($_SERVER['HTTP_REFERER'] ?? '/'));
        }

        if (in_array('guest', $middleware, true) && Auth::check()) {
            $user = Auth::user();
            return Response::redirect((int) ($user['is_admin'] ?? 0) === 1 ? '/admin' : '/');
        }

        if ((in_array('auth', $middleware, true) || in_array('admin', $middleware, true)) && ! Auth::check()) {
            Session::setFlash('error', 'Please sign in first.');
            return Response::redirect('/login');
        }

        if (in_array('admin', $middleware, true)) {
            $user = Auth::user();

            if (! $user || ! User::isAdmin($user)) {
                return Response::html(View::render('public/403', ['title' => 'Forbidden'], 'layouts/public'), 403);
            }
        }

        return null;
    }

    private function call(array|callable $handler, Request $request, array $params): Response
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();

            return $controller->$method($request, ...array_values($params));
        }

        return $handler($request, ...array_values($params));
    }
}

