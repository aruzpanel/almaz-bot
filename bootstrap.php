<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'Bot\\')) {
        return;
    }
    $relative = substr($class, strlen('Bot\\'));
    $path = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

function loadEnvFile(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

loadEnvFile(__DIR__ . '/.env');

use Bot\Config;
use Bot\Database;
use Bot\Repository\ButtonRepository;
use Bot\Repository\ChannelRepository;
use Bot\Repository\PaymentMethodRepository;
use Bot\Repository\SettingsRepository;
use Bot\Repository\TemplateRepository;
use Bot\Repository\UserRepository;
use Bot\Repository\WithdrawalRepository;
use Bot\Router;
use Bot\Services\ReferralService;
use Bot\Services\SubscriptionGate;
use Bot\Support\Templates;
use Bot\TelegramClient;

$config = new Config();
$db = Database::connect($config->dbPath);

$users = new UserRepository($db);
$settings = new SettingsRepository($db);
$channels = new ChannelRepository($db);
$paymentMethods = new PaymentMethodRepository($db);
$templateRepo = new TemplateRepository($db);
$buttonRepo = new ButtonRepository($db);
$withdrawals = new WithdrawalRepository($db);

$telegram = new TelegramClient($config->botToken);
$templates = new Templates($templateRepo);
$gate = new SubscriptionGate($telegram, $channels, $users);
$referrals = new ReferralService($users, $settings, $telegram, $templates);

if ($config->botUsername !== '') {
    $botUsername = $config->botUsername;
} else {
    $me = $telegram->getMe();
    $botUsername = ($me && ($me->ok ?? false)) ? $me->result->username : '';
}

$router = new Router(
    $config,
    $telegram,
    $users,
    $settings,
    $channels,
    $paymentMethods,
    $templateRepo,
    $buttonRepo,
    $withdrawals,
    $templates,
    $gate,
    $referrals,
    $botUsername,
);
