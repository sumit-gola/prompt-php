<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Prompt
{
    public const CATEGORIES = ['portrait', 'product', 'fashion', 'lifestyle', 'art', 'other'];
    public const STATUSES = ['draft', 'pending', 'processing', 'completed', 'failed'];
    public const GENERATION_MODES = ['reference_image', 'auto', 'imported'];

    private const WRITABLE_FIELDS = [
        'user_id',
        'title',
        'prompt',
        'negative_prompt',
        'thumbnail_prompt',
        'thumbnail_path',
        'reference_image_path',
        'generation_mode',
        'source_idea',
        'source_site',
        'source_slug',
        'source_url',
        'source_thumbnail_url',
        'source_published_at',
        'source_modified_at',
        'category',
        'style_notes',
        'ai_provider',
        'ai_model',
        'status',
        'copy_count',
        'error_message',
        'generated_at',
    ];

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM prompts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $prompt = $stmt->fetch();

        return $prompt ?: null;
    }

    public static function findPublic(string $identifier): ?array
    {
        if (ctype_digit($identifier)) {
            $stmt = Database::pdo()->prepare(
                "SELECT * FROM prompts
                 WHERE id = :id AND status = 'completed' AND prompt IS NOT NULL AND prompt <> ''
                 LIMIT 1"
            );
            $stmt->execute(['id' => (int) $identifier]);
        } else {
            $stmt = Database::pdo()->prepare(
                "SELECT * FROM prompts
                 WHERE source_slug = :slug AND status = 'completed' AND prompt IS NOT NULL AND prompt <> ''
                 LIMIT 1"
            );
            $stmt->execute(['slug' => $identifier]);
        }

        $prompt = $stmt->fetch();

        return $prompt ?: null;
    }

    public static function create(array $data): array
    {
        if (empty($data['source_slug']) && ! empty($data['title'])) {
            $data['source_slug'] = self::uniqueSlug((string) $data['title']);
        }

        $data = self::normalize($data);
        $fields = array_keys($data);
        $columns = implode(', ', $fields);
        $placeholders = ':' . implode(', :', $fields);

        $stmt = Database::pdo()->prepare("INSERT INTO prompts ({$columns}, created_at, updated_at) VALUES ({$placeholders}, NOW(), NOW())");
        $stmt->execute($data);

        return self::find((int) Database::pdo()->lastInsertId());
    }

    public static function update(int $id, array $data): bool
    {
        if (array_key_exists('source_slug', $data) && trim((string) $data['source_slug']) === '') {
            $data['source_slug'] = self::uniqueSlug((string) ($data['title'] ?? 'prompt'), $id);
        }

        $data = self::normalize($data);

        if ($data === []) {
            return true;
        }

        $assignments = [];

        foreach ($data as $field => $_) {
            $assignments[] = $field . ' = :' . $field;
        }

        $data['id'] = $id;
        $sql = 'UPDATE prompts SET ' . implode(', ', $assignments) . ', updated_at = NOW() WHERE id = :id';
        $stmt = Database::pdo()->prepare($sql);

        return $stmt->execute($data);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::pdo()->prepare('DELETE FROM prompts WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    public static function publicSearch(array $filters, int $page = 1, int $perPage = 12): array
    {
        [$where, $params] = self::publicWhere($filters);
        $sort = self::publicSort((string) ($filters['sort'] ?? 'newest'));
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $count = Database::pdo()->prepare("SELECT COUNT(*) FROM prompts {$where}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $sql = "SELECT * FROM prompts {$where} ORDER BY {$sort} LIMIT :limit OFFSET :offset";
        $stmt = Database::pdo()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public static function latestCompleted(int $limit = 8): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM prompts
             WHERE status = 'completed' AND prompt IS NOT NULL AND prompt <> ''
             ORDER BY COALESCE(generated_at, created_at) DESC, id DESC
             LIMIT :limit"
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function sitemapCompleted(): array
    {
        $stmt = Database::pdo()->query(
            "SELECT id, source_slug, updated_at, generated_at
             FROM prompts
             WHERE status = 'completed' AND prompt IS NOT NULL AND prompt <> ''
             ORDER BY updated_at DESC"
        );

        return $stmt->fetchAll();
    }

    public static function related(array $prompt, int $limit = 4): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM prompts
             WHERE status = 'completed'
               AND prompt IS NOT NULL
               AND prompt <> ''
               AND id <> :id
               AND category = :category
             ORDER BY copy_count DESC, COALESCE(generated_at, created_at) DESC
             LIMIT :limit"
        );
        $stmt->bindValue('id', (int) $prompt['id'], PDO::PARAM_INT);
        $stmt->bindValue('category', (string) $prompt['category']);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function incrementCopyCount(int $id): ?array
    {
        $update = Database::pdo()->prepare(
            "UPDATE prompts SET copy_count = copy_count + 1, updated_at = NOW()
             WHERE id = :id AND status = 'completed' AND prompt IS NOT NULL AND prompt <> ''"
        );
        $update->execute(['id' => $id]);

        if ($update->rowCount() < 1) {
            return null;
        }

        return self::find($id);
    }

    public static function adminSearch(array $filters, int $page = 1, int $perPage = 20): array
    {
        [$where, $params] = self::adminWhere($filters);
        $sort = self::adminSort((string) ($filters['sort'] ?? 'newest'));
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $count = Database::pdo()->prepare("SELECT COUNT(*) FROM prompts {$where}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $sql = "SELECT * FROM prompts {$where} ORDER BY {$sort} LIMIT :limit OFFSET :offset";
        $stmt = Database::pdo()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public static function stats(): array
    {
        $stmt = Database::pdo()->query(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'completed') AS completed,
                SUM(status = 'pending') AS pending,
                SUM(status = 'processing') AS processing,
                SUM(status = 'failed') AS failed,
                SUM(status = 'draft') AS draft,
                COALESCE(SUM(copy_count), 0) AS copies
             FROM prompts"
        );

        return $stmt->fetch() ?: [
            'total' => 0,
            'completed' => 0,
            'pending' => 0,
            'processing' => 0,
            'failed' => 0,
            'draft' => 0,
            'copies' => 0,
        ];
    }

    public static function bulkByIds(array $ids): array
    {
        $ids = self::cleanIds($ids);

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare("SELECT * FROM prompts WHERE id IN ({$placeholders})");
        $stmt->execute($ids);

        return $stmt->fetchAll();
    }

    public static function bulkDelete(array $ids): int
    {
        $ids = self::cleanIds($ids);

        if ($ids === []) {
            return 0;
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare("DELETE FROM prompts WHERE id IN ({$placeholders})");
        $stmt->execute($ids);

        return $stmt->rowCount();
    }

    public static function bulkUpdate(array $ids, array $fields): int
    {
        $ids = self::cleanIds($ids);
        $fields = self::normalize($fields);

        if ($ids === [] || $fields === []) {
            return 0;
        }

        $assignments = [];
        $params = [];

        foreach ($fields as $field => $value) {
            $assignments[] = $field . ' = ?';
            $params[] = $value;
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = 'UPDATE prompts SET ' . implode(', ', $assignments) . ", updated_at = NOW() WHERE id IN ({$placeholders})";
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(array_merge($params, $ids));

        return $stmt->rowCount();
    }

    public static function decodeStyleNotes(?string $json): array
    {
        if (! $json) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function publicIdentifier(array $prompt): string
    {
        return (string) ($prompt['source_slug'] ?: $prompt['id']);
    }

    public static function slugify(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?: 'prompt';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'prompt';
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = self::slugify($title);
        $slug = $base;
        $index = 2;

        while (self::slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . $index;
            $index++;
        }

        return $slug;
    }

    private static function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM prompts WHERE source_slug = :slug';
        $params = ['slug' => $slug];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $ignoreId;
        }

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    private static function normalize(array $data): array
    {
        $normalized = [];

        foreach ($data as $field => $value) {
            if (! in_array($field, self::WRITABLE_FIELDS, true)) {
                continue;
            }

            if ($field === 'style_notes' && is_array($value)) {
                $value = json_encode($value, JSON_THROW_ON_ERROR);
            }

            if (in_array($field, ['copy_count', 'user_id'], true) && $value !== null && $value !== '') {
                $value = (int) $value;
            }

            $normalized[$field] = $value === '' ? null : $value;
        }

        return $normalized;
    }

    private static function publicWhere(array $filters): array
    {
        $where = ["status = 'completed'", "prompt IS NOT NULL", "prompt <> ''"];
        $params = [];

        if (! empty($filters['q'])) {
            $term = '%' . trim((string) $filters['q']) . '%';
            $where[] = '(title LIKE :q_title OR prompt LIKE :q_prompt OR category LIKE :q_category)';
            $params['q_title'] = $term;
            $params['q_prompt'] = $term;
            $params['q_category'] = $term;
        }

        if (! empty($filters['category']) && in_array((string) $filters['category'], self::CATEGORIES, true)) {
            $where[] = 'category = :category';
            $params['category'] = (string) $filters['category'];
        }

        return ['WHERE ' . implode(' AND ', $where), $params];
    }

    private static function adminWhere(array $filters): array
    {
        $where = ['1 = 1'];
        $params = [];

        if (! empty($filters['q'])) {
            $term = '%' . trim((string) $filters['q']) . '%';
            $where[] = '(title LIKE :q_title OR prompt LIKE :q_prompt OR source_site LIKE :q_source_site OR source_url LIKE :q_source_url OR source_slug LIKE :q_source_slug)';
            $params['q_title'] = $term;
            $params['q_prompt'] = $term;
            $params['q_source_site'] = $term;
            $params['q_source_url'] = $term;
            $params['q_source_slug'] = $term;
        }

        if (! empty($filters['status']) && in_array((string) $filters['status'], self::STATUSES, true)) {
            $where[] = 'status = :status';
            $params['status'] = (string) $filters['status'];
        }

        if (! empty($filters['category']) && in_array((string) $filters['category'], self::CATEGORIES, true)) {
            $where[] = 'category = :category';
            $params['category'] = (string) $filters['category'];
        }

        if (! empty($filters['generation_mode']) && in_array((string) $filters['generation_mode'], self::GENERATION_MODES, true)) {
            $where[] = 'generation_mode = :generation_mode';
            $params['generation_mode'] = (string) $filters['generation_mode'];
        }

        if (! empty($filters['source'])) {
            $source = '%' . trim((string) $filters['source']) . '%';
            $where[] = '(source_site LIKE :source_site OR source_url LIKE :source_url OR source_slug LIKE :source_slug)';
            $params['source_site'] = $source;
            $params['source_url'] = $source;
            $params['source_slug'] = $source;
        }

        if (! empty($filters['date_from'])) {
            $where[] = 'created_at >= :date_from';
            $params['date_from'] = trim((string) $filters['date_from']) . ' 00:00:00';
        }

        if (! empty($filters['date_to'])) {
            $where[] = 'created_at <= :date_to';
            $params['date_to'] = trim((string) $filters['date_to']) . ' 23:59:59';
        }

        return ['WHERE ' . implode(' AND ', $where), $params];
    }

    private static function publicSort(string $sort): string
    {
        return match ($sort) {
            'oldest' => 'created_at ASC, id ASC',
            'popular', 'most_copied' => 'copy_count DESC, COALESCE(generated_at, created_at) DESC, id DESC',
            'category' => 'category ASC, title ASC',
            default => 'COALESCE(generated_at, created_at) DESC, id DESC',
        };
    }

    private static function adminSort(string $sort): string
    {
        return match ($sort) {
            'oldest' => 'created_at ASC, id ASC',
            'copies' => 'copy_count DESC, updated_at DESC',
            'title' => 'title ASC',
            'status' => 'status ASC, updated_at DESC',
            'category' => 'category ASC, title ASC',
            'generated' => 'generated_at DESC, updated_at DESC',
            default => 'created_at DESC, id DESC',
        };
    }

    private static function cleanIds(array $ids): array
    {
        $ids = array_map('intval', $ids);
        $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));

        return $ids;
    }
}
