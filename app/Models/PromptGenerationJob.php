<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PromptGenerationJob
{
    public static function create(int $promptId, string $type, array $payload = []): array
    {
        $stmt = Database::pdo()->prepare(
            "INSERT INTO prompt_generation_jobs (prompt_id, type, payload, status, attempts, available_at, created_at, updated_at)
             VALUES (:prompt_id, :type, :payload, 'pending', 0, NOW(), NOW(), NOW())"
        );
        $stmt->execute([
            'prompt_id' => $promptId,
            'type' => $type,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
        ]);

        return self::find((int) Database::pdo()->lastInsertId());
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM prompt_generation_jobs WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $job = $stmt->fetch();

        return $job ?: null;
    }

    public static function nextPending(): ?array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM prompt_generation_jobs
             WHERE status = 'pending' AND available_at <= NOW()
             ORDER BY id ASC
             LIMIT 1"
        );
        $stmt->execute();
        $job = $stmt->fetch();

        return $job ?: null;
    }

    public static function markProcessing(int $id): void
    {
        $stmt = Database::pdo()->prepare(
            "UPDATE prompt_generation_jobs
             SET status = 'processing', attempts = attempts + 1, started_at = NOW(), updated_at = NOW()
             WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    public static function markCompleted(int $id): void
    {
        $stmt = Database::pdo()->prepare(
            "UPDATE prompt_generation_jobs
             SET status = 'completed', finished_at = NOW(), error_message = NULL, updated_at = NOW()
             WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    public static function markFailed(int $id, string $message): void
    {
        $stmt = Database::pdo()->prepare(
            "UPDATE prompt_generation_jobs
             SET status = 'failed', finished_at = NOW(), error_message = :error_message, updated_at = NOW()
             WHERE id = :id"
        );
        $stmt->execute([
            'id' => $id,
            'error_message' => mb_substr($message, 0, 1000),
        ]);
    }

    public static function pendingCount(): int
    {
        $stmt = Database::pdo()->query("SELECT COUNT(*) FROM prompt_generation_jobs WHERE status = 'pending'");

        return (int) $stmt->fetchColumn();
    }
}

