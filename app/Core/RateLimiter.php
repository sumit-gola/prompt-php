<?php

declare(strict_types=1);

namespace App\Core;

use DateInterval;
use DateTimeImmutable;

final class RateLimiter
{
    public static function hit(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $pdo = Database::pdo();
        $now = new DateTimeImmutable();
        $expiresAt = $now->add(new DateInterval('PT' . $decaySeconds . 'S'))->format('Y-m-d H:i:s');

        $stmt = $pdo->prepare('SELECT * FROM rate_limits WHERE key_name = :key_name LIMIT 1');
        $stmt->execute(['key_name' => $key]);
        $row = $stmt->fetch();

        if ($row && strtotime((string) $row['expires_at']) <= time()) {
            $delete = $pdo->prepare('DELETE FROM rate_limits WHERE key_name = :key_name');
            $delete->execute(['key_name' => $key]);
            $row = false;
        }

        if ($row && (int) $row['attempts'] >= $maxAttempts) {
            return false;
        }

        if ($row) {
            $update = $pdo->prepare('UPDATE rate_limits SET attempts = attempts + 1, updated_at = NOW() WHERE key_name = :key_name');
            $update->execute(['key_name' => $key]);

            return true;
        }

        $insert = $pdo->prepare('INSERT INTO rate_limits (key_name, attempts, expires_at, created_at, updated_at) VALUES (:key_name, 1, :expires_at, NOW(), NOW())');
        $insert->execute(['key_name' => $key, 'expires_at' => $expiresAt]);

        return true;
    }
}

