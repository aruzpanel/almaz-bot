<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../bootstrap.php';

use Bot\Support\Keyboards;

/**
 * Validates Telegram WebApp initData per the algorithm documented at
 * https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app
 *
 * @return array<string,string>|null the parsed fields, or null if invalid/missing
 */
function validateInitData(string $initData, string $botToken): ?array
{
    if ($initData === '') {
        return null;
    }

    parse_str($initData, $data);
    if (!isset($data['hash'])) {
        return null;
    }

    $hash = $data['hash'];
    unset($data['hash']);
    ksort($data);

    $pairs = [];
    foreach ($data as $key => $value) {
        $pairs[] = "$key=$value";
    }
    $dataCheckString = implode("\n", $pairs);

    $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
    $computedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

    if (!hash_equals($computedHash, $hash)) {
        return null;
    }

    return $data;
}

function fail(int $code, string $message): never
{
    http_response_code($code);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$initData = $_POST['initData'] ?? '';
$validated = validateInitData($initData, $config->botToken);
if ($validated === null) {
    fail(401, 'Tasdiqlanmadi. Iltimos, sahifani botdan qayta oching.');
}

$tgUser = json_decode($validated['user'] ?? '{}', true);
$userId = (int) ($tgUser['id'] ?? 0);
if ($userId === 0) {
    fail(400, 'Foydalanuvchi aniqlanmadi.');
}

$user = $users->find($userId);
if ($user === null) {
    fail(404, "Botda ro'yxatdan o'tmagansiz. Avval botga /start yozing.");
}

$action = $_POST['action'] ?? 'profile';
$currency = $settings->get('valyuta', "so'm");

if ($action === 'profile') {
    $top = array_map(
        static fn (array $r) => [
            'id' => (int) $r['id'],
            'name' => ($r['first_name'] ?? '') !== '' ? $r['first_name'] : 'Foydalanuvchi',
            'count' => (int) $r['referral_count'],
            'is_you' => (int) $r['id'] === $userId,
        ],
        $users->topReferrers(10)
    );

    echo json_encode([
        'first_name' => $user->firstName ?: "Do'st",
        'balance' => $user->balance,
        'withdrawn' => $user->withdrawn,
        'referral_count' => $user->referralCount,
        'currency' => $currency,
        'top' => $top,
        'reflink' => "https://t.me/{$botUsername}?start={$userId}",
        'refpay' => (int) $settings->get('taklif', '250'),
        'min_withdraw' => (int) $settings->get('narx', '3000'),
        'payment_methods' => $paymentMethods->all(),
        'payments_channel_ready' => $settings->get('vazifa', 'Kiritilmagan') !== 'Kiritilmagan',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'withdraw') {
    if (!$settings->get('vazifa') || $settings->get('vazifa') === 'Kiritilmagan') {
        fail(422, "To'lovlar kanali ulanmagan!");
    }

    $wallet = trim((string) ($_POST['payment_method'] ?? ''));
    $gameId = trim((string) ($_POST['game_id'] ?? ''));
    $amount = (int) ($_POST['amount'] ?? 0);
    $minAmount = (int) $settings->get('narx', '3000');

    if ($wallet === '' || !in_array($wallet, $paymentMethods->all(), true)) {
        fail(422, "To'lov usulini tanlang.");
    }
    if ($gameId === '' || !ctype_digit($gameId)) {
        fail(422, 'Free Fire ID raqamini kiriting.');
    }
    if ($amount < $minAmount) {
        fail(422, "Minimal miqdor: {$minAmount} {$currency}");
    }
    if ($user->balance < $amount) {
        fail(422, 'Balansingiz yetarli emas.');
    }

    $users->adjustBalance($userId, -$amount);
    $users->adjustWithdrawn($userId, $amount);
    $withdrawalId = $withdrawals->create($userId, $wallet, $gameId, $amount);

    $safeUsername = htmlspecialchars((string) $user->username, ENT_QUOTES);
    $telegram->sendMessage($config->adminId,
        "💵 <a href='https://t.me/{$safeUsername}'>{$userId}</a> <b>almaz yechib olmoqchi!</b> (Mini App)\n\n"
        . "• <b>Yechish turi:</b> {$wallet}\n"
        . "• <b>almaz miqdori:</b> {$amount}\n"
        . "• <b>Free Fire ID raqami:</b> {$gameId}\n\n"
        . "Foydalanuvchini almazini to'lab bermoqchi bo'lsangiz ✅ <b>To'landi</b> tugmasini bosing!",
        [
            'disable_web_page_preview' => true,
            'reply_markup' => Keyboards::inline([
                [Keyboards::button('🔔 Banlash', ['callback_data' => "wd:ban:{$withdrawalId}"])],
                [
                    Keyboards::button("✅ To'landi", ['callback_data' => "wd:paid:{$withdrawalId}"]),
                    Keyboards::button("❌ To'lanmadi", ['callback_data' => "wd:unpaid:{$withdrawalId}"]),
                ],
            ]),
        ]
    );

    $telegram->sendMessage($userId, "✅ <b>So'rovingiz qabul qilindi!</b> Admin tez orada ko'rib chiqadi.");

    echo json_encode(['ok' => true, 'balance' => $user->balance - $amount], JSON_UNESCAPED_UNICODE);
    exit;
}

fail(400, "Noma'lum amal.");
