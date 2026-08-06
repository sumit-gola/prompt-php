<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Prompt;
use App\Models\PromptGenerationJob;
use App\Services\Ai\AiProviderFactory;

final class PromptGenerationService
{
    public function processNext(): bool
    {
        $job = PromptGenerationJob::nextPending();

        if (! $job) {
            return false;
        }

        $this->process($job);

        return true;
    }

    public function process(array $job): void
    {
        PromptGenerationJob::markProcessing((int) $job['id']);

        $prompt = Prompt::find((int) $job['prompt_id']);

        if (! $prompt) {
            PromptGenerationJob::markFailed((int) $job['id'], 'Prompt no longer exists.');
            return;
        }

        Prompt::update((int) $prompt['id'], [
            'status' => 'processing',
            'error_message' => null,
        ]);

        try {
            $payload = json_decode((string) ($job['payload'] ?? '{}'), true);
            $payload = is_array($payload) ? $payload : [];
            $provider = AiProviderFactory::make();

            if ((string) $job['type'] === 'reference_image') {
                $result = $provider->generateFromReference($prompt, $payload['reference_images'] ?? []);
            } else {
                $brief = (string) ($payload['brief'] ?? $prompt['source_idea'] ?? '');
                $result = $provider->generateFromBrief($prompt, $brief);
            }

            if (trim((string) ($result['prompt'] ?? '')) === '') {
                throw new \RuntimeException('AI provider returned an empty prompt.');
            }

            Prompt::update((int) $prompt['id'], [
                'prompt' => $result['prompt'],
                'negative_prompt' => $result['negative_prompt'] ?? null,
                'thumbnail_prompt' => $result['thumbnail_prompt'] ?? null,
                'style_notes' => $result['style_notes'] ?? [],
                'ai_provider' => $result['ai_provider'] ?? env('AI_PROVIDER', 'unknown'),
                'ai_model' => $result['ai_model'] ?? env('AI_MODEL', null),
                'status' => 'completed',
                'error_message' => null,
                'generated_at' => date('Y-m-d H:i:s'),
            ]);

            PromptGenerationJob::markCompleted((int) $job['id']);
        } catch (\Throwable $exception) {
            $message = $exception->getMessage();
            Prompt::update((int) $prompt['id'], [
                'status' => 'failed',
                'error_message' => mb_substr($message, 0, 1000),
            ]);
            PromptGenerationJob::markFailed((int) $job['id'], $message);
        }
    }
}

