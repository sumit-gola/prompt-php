<?php

declare(strict_types=1);

namespace App\Services\Ai;

interface AiProviderInterface
{
    public function generateFromReference(array $prompt, array $imagePaths): array;

    public function generateFromBrief(array $prompt, string $brief): array;
}

