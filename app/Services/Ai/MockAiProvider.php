<?php

declare(strict_types=1);

namespace App\Services\Ai;

final class MockAiProvider implements AiProviderInterface
{
    public function generateFromReference(array $prompt, array $imagePaths): array
    {
        $title = trim((string) ($prompt['title'] ?? 'Reference image prompt'));
        $count = count($imagePaths);

        return $this->result(
            'Use the ' . $count . ' reference image' . ($count === 1 ? '' : 's') . ' to create a polished AI image of ' . $title . '. Match the composition, lighting, subject proportions, color palette, and surface detail while improving clarity and commercial finish.'
        );
    }

    public function generateFromBrief(array $prompt, string $brief): array
    {
        return $this->result(
            'Create a high-quality AI image from this brief: ' . trim($brief) . '. Use intentional composition, natural lighting, crisp detail, and a finished editorial look.'
        );
    }

    private function result(string $text): array
    {
        return [
            'prompt' => $text,
            'negative_prompt' => 'low resolution, blurry, distorted anatomy, extra limbs, watermark, text artifacts, oversaturated colors',
            'thumbnail_prompt' => 'Clean preview image for: ' . mb_substr($text, 0, 120),
            'style_notes' => [
                'tone' => 'clean commercial',
                'lighting' => 'soft directional light',
                'detail' => 'crisp texture and realistic depth',
            ],
            'ai_provider' => 'mock',
            'ai_model' => 'local-mock',
        ];
    }
}

