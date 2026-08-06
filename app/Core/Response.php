<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public function __construct(
        private readonly string $body,
        private readonly int $status = 200,
        private readonly array $headers = []
    ) {
    }

    public static function html(string $body, int $status = 200, array $headers = []): self
    {
        return new self($body, $status, array_merge(['Content-Type' => 'text/html; charset=UTF-8'], $headers));
    }

    public static function text(string $body, int $status = 200, array $headers = []): self
    {
        return new self($body, $status, array_merge(['Content-Type' => 'text/plain; charset=UTF-8'], $headers));
    }

    public static function xml(string $body, int $status = 200, array $headers = []): self
    {
        return new self($body, $status, array_merge(['Content-Type' => 'application/xml; charset=UTF-8'], $headers));
    }

    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        return new self(json_encode($data, JSON_THROW_ON_ERROR), $status, array_merge(['Content-Type' => 'application/json; charset=UTF-8'], $headers));
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self('', $status, ['Location' => $location]);
    }

    public function send(?Request $request = null): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        if ($request && $request->realMethod() === 'HEAD') {
            return;
        }

        echo $this->body;
    }
}
