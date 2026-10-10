<?php

declare(strict_types=1);

// Safety net for broadcasts: resumes one that was interrupted (e.g. PHP-FPM restart).
// Normally the webhook starts sending right away; run this from cron as the web user:
//   * * * * * php /var/www/bot.danatpay.uz/broadcast_worker.php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/bootstrap.php';

set_time_limit(0);
$broadcastSender->run();
