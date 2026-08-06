<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class User
{
    private static ?bool $usesPasswordHash = null;

    public static function find(int $id): ?array
    {
        $passwordColumn = self::passwordColumn();
        $stmt = Database::pdo()->prepare("SELECT id, name, email, {$passwordColumn} AS password_hash, is_admin, created_at, updated_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $passwordColumn = self::passwordColumn();
        $stmt = Database::pdo()->prepare("SELECT id, name, email, {$passwordColumn} AS password_hash, is_admin, created_at, updated_at FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => mb_strtolower(trim($email))]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function create(string $name, string $email, string $password, bool $isAdmin = false): array
    {
        $passwordColumn = self::passwordColumn();
        $stmt = Database::pdo()->prepare(
            "INSERT INTO users (name, email, {$passwordColumn}, is_admin, created_at, updated_at)
             VALUES (:name, :email, :password_hash, :is_admin, NOW(), NOW())"
        );

        $stmt->execute([
            'name' => trim($name),
            'email' => mb_strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'is_admin' => $isAdmin ? 1 : 0,
        ]);

        return self::find((int) Database::pdo()->lastInsertId());
    }

    public static function countAdmins(): int
    {
        $stmt = Database::pdo()->query('SELECT COUNT(*) FROM users WHERE is_admin = 1');

        return (int) $stmt->fetchColumn();
    }

    public static function isAdmin(array $user): bool
    {
        return (int) ($user['is_admin'] ?? 0) === 1;
    }

    private static function passwordColumn(): string
    {
        if (self::$usesPasswordHash !== null) {
            return self::$usesPasswordHash ? 'password_hash' : 'password';
        }

        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = 'users'
               AND column_name = 'password_hash'"
        );
        $stmt->execute();
        self::$usesPasswordHash = (int) $stmt->fetchColumn() > 0;

        return self::$usesPasswordHash ? 'password_hash' : 'password';
    }
}
