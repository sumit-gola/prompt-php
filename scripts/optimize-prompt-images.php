<?php

declare(strict_types=1);

use App\Core\Database;
use App\Services\PromptImageService;

require dirname(__DIR__) . '/bootstrap/app.php';

$options = getopt('', ['apply', 'force', 'id:', 'limit:', 'offset:', 'all-statuses']);
$apply = array_key_exists('apply', $options);
$force = array_key_exists('force', $options);
$id = max(0, (int) ($options['id'] ?? 0));
$limit = max(0, (int) ($options['limit'] ?? 0));
$offset = max(0, (int) ($options['offset'] ?? 0));
$allStatuses = array_key_exists('all-statuses', $options);

if ($offset > 0 && $limit === 0) {
    fwrite(STDERR, "--offset requires --limit.\n");
    exit(2);
}

$where = ["(thumbnail_path IS NOT NULL AND thumbnail_path <> '' OR reference_image_path IS NOT NULL AND reference_image_path <> '')"];
$params = [];

if (! $allStatuses) {
    $where[] = "status = 'completed'";
}

if ($id > 0) {
    $where[] = 'id = :id';
    $params['id'] = $id;
}

$sql = 'SELECT id, title, source_slug, category, thumbnail_path, reference_image_path '
    . 'FROM prompts WHERE ' . implode(' AND ', $where) . ' ORDER BY id';

if ($limit > 0) {
    $sql .= ' LIMIT ' . $limit;

    if ($offset > 0) {
        $sql .= ' OFFSET ' . $offset;
    }
}

$statement = Database::pdo()->prepare($sql);
$statement->execute($params);
$prompts = $statement->fetchAll();
$optimized = 0;
$failed = 0;

foreach ($prompts as $prompt) {
    $target = PromptImageService::plannedPath($prompt);

    if (! $apply) {
        printf("PLAN #%d %s\n", (int) $prompt['id'], $target);
        continue;
    }

    $metadata = PromptImageService::optimize($prompt, null, $force);

    if ($metadata === null) {
        $failed++;
        fprintf(STDERR, "FAIL #%d %s\n", (int) $prompt['id'], (string) $prompt['title']);
        continue;
    }

    $optimized++;
    printf(
        "OK #%d %s %dx%d\n",
        (int) $prompt['id'],
        (string) $metadata['path'],
        (int) $metadata['width'],
        (int) $metadata['height']
    );
}

printf(
    "%s: %d prompt(s), %d optimized, %d failed.\n",
    $apply ? 'Complete' : 'Dry run',
    count($prompts),
    $optimized,
    $failed
);

exit($failed > 0 ? 1 : 0);
