<?php

declare(strict_types=1);

$router = require dirname(__DIR__) . '/bootstrap/app.php';

$schema = file_get_contents(__DIR__ . '/migrations/schema.sql');

if ($schema === false) {
    fwrite(STDERR, "Could not read schema.sql\n");
    exit(1);
}

try {
    $pdo = \App\Core\Database::pdo();
    $pdo->exec($schema);

    if (! $pdo->query("SHOW COLUMNS FROM prompts LIKE 'tested_models'")->fetch()) {
        $pdo->exec('ALTER TABLE prompts ADD tested_models VARCHAR(500) NULL AFTER ai_model');
    }

    if (! $pdo->query("SHOW COLUMNS FROM prompts LIKE 'reviewed_at'")->fetch()) {
        $pdo->exec('ALTER TABLE prompts ADD reviewed_at DATETIME NULL AFTER tested_models');
    }

    $backfilledSlugs = \App\Models\Prompt::backfillMissingSlugs();
    echo "Database schema applied.\n";
    echo "Prompt slugs backfilled: {$backfilledSlugs}.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Migration failed: " . $exception->getMessage() . "\n");
    exit(1);
}
