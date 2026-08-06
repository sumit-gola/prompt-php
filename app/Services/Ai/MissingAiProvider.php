<?php

declare(strict_types=1);

namespace App\Services\Ai;

final class MissingAiProvider implements AiProviderInterface
{
    public function generateFromReference(array $prompt, array $imagePaths): array
    {
        throw new \RuntimeException('AI provider is not configured. Set AI_PROVIDER and provider credentials in the environment.');
    }

    public function generateFromBrief(array $prompt, string $brief): array
    {
        throw new \RuntimeException('AI provider is not configured. Set AI_PROVIDER and provider credentials in the environment.');
    }
}

