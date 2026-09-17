<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Bot\Update;

if ($config->webhookSecret !== '') {
    $header = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    if (!hash_equals($config->webhookSecret, $header)) {
        http_response_code(403);
        exit;
    }
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || $rawBody === '') {
    http_response_code(200);
    exit;
}

try {
    $update = Update::fromRawBody($rawBody);
    $router->dispatch($update);
} catch (\Throwable $e) {
    error_log('Webhook error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
}

http_response_code(200);
