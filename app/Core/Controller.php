<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $template, array $data = [], string $layout = 'layouts/public', int $status = 200): Response
    {
        return Response::html(View::render($template, $data, $layout), $status);
    }

    protected function adminView(string $template, array $data = [], int $status = 200): Response
    {
        return Response::html(View::render($template, $data, 'layouts/admin'), $status);
    }

    protected function authView(string $template, array $data = [], int $status = 200): Response
    {
        return Response::html(View::render($template, $data, 'layouts/auth'), $status);
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect($path);
    }

    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function backWithErrors(array $errors, array $old = []): Response
    {
        Session::setFlash('errors', $errors);
        Session::put('old', $old);

        return Response::redirect((string) ($_SERVER['HTTP_REFERER'] ?? '/'));
    }
}

