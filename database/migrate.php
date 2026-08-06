<?php

declare(strict_types=1);

$router = require dirname(__DIR__) . '/bootstrap/app.php';

$schema = file_get_contents(__DIR__ . '/migrations/schema.sql');

if ($schema === false) {
    fwrite(STDERR, "Could not read schema.sql\n");
    exit(1);
}

try {
    \App\Core\Database::pdo()->exec($schema);
    echo "Database schema applied.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Migration failed: " . $exception->getMessage() . "\n");
    exit(1);
}

