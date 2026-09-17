<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Bot\Defaults;

$legacyPath = $argv[1] ?? null;
if ($legacyPath === null || !is_dir($legacyPath)) {
    fwrite(STDERR, "Foydalanish: php migrate_from_legacy.php <eski-bot-papkasi-yoli>\n");
    exit(1);
}
$legacyPath = rtrim($legacyPath, '/\\');

function readFileOrNull(string $path): ?string
{
    if (!is_file($path)) {
        return null;
    }
    $content = file_get_contents($path);

    return $content === false ? null : trim($content);
}

echo "Eski papka: $legacyPath\n";

// --- settings ---
$settingsMap = [
    'admin/taklif.txt' => 'taklif',
    'admin/valyuta.txt' => 'valyuta',
    'admin/narx.txt' => 'narx',
    'admin/vazifa.txt' => 'vazifa',
    'admin/user.txt' => 'user',
];
foreach ($settingsMap as $file => $key) {
    $value = readFileOrNull("$legacyPath/$file");
    if ($value !== null && $value !== '') {
        $settings->set($key, $value);
        echo "settings.$key = $value\n";
    }
}

// --- channels ---
$kanalContent = readFileOrNull("$legacyPath/admin/kanal.txt");
if ($kanalContent !== null) {
    $channels->clear();
    $count = 0;
    foreach (explode("\n", $kanalContent) as $line) {
        $line = trim($line);
        if ($line === '' || !str_contains($line, '@')) {
            continue;
        }
        $username = trim(explode('@', $line, 2)[1] ?? '');
        if ($username !== '') {
            $channels->add($username);
            $count++;
        }
    }
    echo "channels: $count ta import qilindi\n";
}

// --- payment methods ---
$turiContent = readFileOrNull("$legacyPath/number/turi.txt");
if ($turiContent !== null) {
    $count = 0;
    foreach (explode("\n", $turiContent) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $paymentMethods->add($line);
        $count++;
    }
    echo "payment_methods: $count ta import qilindi\n";
}

// --- templates (matn/*.txt) ---
$templateCount = 0;
foreach (array_keys(Defaults::templates()) as $code) {
    $content = readFileOrNull("$legacyPath/matn/$code.txt");
    if ($content !== null && $content !== '' && $content !== $code) {
        $templateRepo->set($code, $content);
        $templateCount++;
    }
}
echo "templates: $templateCount ta moslashtirilgan matn import qilindi\n";

// --- buttons (tugma/*.txt) ---
$buttonCount = 0;
foreach (array_keys(Defaults::buttons()) as $code) {
    $content = readFileOrNull("$legacyPath/tugma/$code.txt");
    if ($content !== null && $content !== '' && $content !== $code) {
        $buttonRepo->set($code, $content);
        $buttonCount++;
    }
}
echo "buttons: $buttonCount ta moslashtirilgan tugma import qilindi\n";

// --- users ---
$ids = [];

$azoContent = readFileOrNull("$legacyPath/azo.dat");
if ($azoContent !== null) {
    foreach (explode("\n", $azoContent) as $line) {
        $line = trim($line);
        if ($line !== '' && is_numeric($line)) {
            $ids[(int) $line] = true;
        }
    }
}

foreach (['pul', 'odam', 'ban', 'number'] as $dir) {
    $full = "$legacyPath/$dir";
    if (!is_dir($full)) {
        continue;
    }
    foreach (scandir($full) as $file) {
        if (preg_match('/^(\d+)\.(txt|dat)$/', $file, $m) === 1) {
            $ids[(int) $m[1]] = true;
        }
    }
}

$userCount = 0;
foreach (array_keys($ids) as $id) {
    $user = $users->findOrCreate($id, null, null, null);

    $balance = readFileOrNull("$legacyPath/pul/$id.txt");
    $withdrawn = readFileOrNull("$legacyPath/pul/$id.dat");
    $referralCount = readFileOrNull("$legacyPath/odam/$id.txt");
    $ban = readFileOrNull("$legacyPath/ban/$id.txt");
    $phone = readFileOrNull("$legacyPath/number/$id.txt");
    $clicks = readFileOrNull("$legacyPath/admin/checks/$id.txt");

    $user->balance = is_numeric($balance) ? (int) $balance : 0;
    $user->withdrawn = is_numeric($withdrawn) ? (int) $withdrawn : 0;
    $user->referralCount = is_numeric($referralCount) ? (int) $referralCount : 0;
    $user->isBanned = $ban === 'ban';
    $user->phone = ($phone !== null && $phone !== '') ? $phone : null;
    $user->joinCheckClicks = is_numeric($clicks) ? (int) $clicks : 0;

    $users->save($user);
    $userCount++;
}

echo "users: $userCount ta foydalanuvchi import qilindi\n";
echo "Migratsiya yakunlandi.\n";
