<?php

declare(strict_types=1);

namespace App\Services\Ai;

final class AiProviderFactory
{
    public static function make(): AiProviderInterface
    {
        $provider = mb_strtolower((string) env('AI_PROVIDER', 'none'));

        return match ($provider) {
            'mock' => new MockAiProvider(),
            default => new MissingAiProvider(),
        };
    }
}

