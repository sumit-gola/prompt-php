<?php

declare(strict_types=1);

$router = require dirname(__DIR__) . '/bootstrap/app.php';

use App\Services\PromptGenerationService;

$options = getopt('', ['once', 'sleep::', 'limit::']);
$once = array_key_exists('once', $options);
$sleep = max(1, (int) ($options['sleep'] ?? 5));
$limit = max(1, (int) ($options['limit'] ?? 500));
$processed = 0;
$service = new PromptGenerationService();

while ($processed < $limit) {
    try {
        $handled = $service->processNext();
    } catch (Throwable $exception) {
        fwrite(STDERR, '[' . date('c') . '] Queue worker error: ' . $exception->getMessage() . "\n");
        $handled = false;
    }

    if ($handled) {
        $processed++;
        echo '[' . date('c') . "] Processed job {$processed}\n";
        continue;
    }

    if ($once) {
        echo "No pending jobs.\n";
        break;
    }

    sleep($sleep);
}

