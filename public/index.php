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
$payload = $rawBody === false || $rawBody === '' ? null : json_decode($rawBody);
if (!$payload instanceof \stdClass) {
    http_response_code(200);
    exit;
}

// Telegram re-sends an update it considers unanswered; handle each update_id once.
if (isset($payload->update_id) && !$processedUpdates->claim((int) $payload->update_id)) {
    http_response_code(200);
    exit;
}

try {
    $router->dispatch(Update::fromStdClass($payload));
} catch (\Throwable $e) {
    error_log('Webhook error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
}

http_response_code(200);

// Answer Telegram first, then send any queued broadcast from this worker.
// BroadcastSender holds a lock, so concurrent requests return here immediately.
if ($broadcasts->hasActive()) {
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    ignore_user_abort(true);
    set_time_limit(0);
    try {
        $broadcastSender->run();
    } catch (\Throwable $e) {
        error_log('Broadcast error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    }
}
