<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$stmt = $db->query('SELECT id FROM users WHERE first_name IS NULL');
$ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

echo 'Jami tekshiriladigan: ' . count($ids) . "\n";

$batchSize = 25;
$updated = 0;
$failed = 0;
$processed = 0;

$updateStmt = $db->prepare('UPDATE users SET first_name = ?, last_name = ?, username = ? WHERE id = ?');

foreach (array_chunk($ids, $batchSize) as $batch) {
    $requests = [];
    foreach ($batch as $id) {
        $requests[(string) $id] = [
            'method' => 'getChat',
            'params' => ['chat_id' => $id],
        ];
    }

    $results = $telegram->callMulti($requests);

    foreach ($results as $idStr => $result) {
        $processed++;
        if ($result && ($result->ok ?? false)) {
            $firstName = $result->result->first_name ?? null;
            $lastName = $result->result->last_name ?? null;
            $username = $result->result->username ?? null;
            if ($firstName !== null) {
                $updateStmt->execute([$firstName, $lastName, $username, (int) $idStr]);
                $updated++;
            } else {
                $failed++;
            }
        } else {
            $failed++;
        }
    }

    echo "Jarayon: $processed / " . count($ids) . " (yangilandi: $updated, muvaffaqiyatsiz: $failed)\n";
    usleep(400000); // ~0.4s pause between batches to stay well within rate limits
}

echo "Yakunlandi. Yangilandi: $updated, muvaffaqiyatsiz (bloklagan/o'chirilgan): $failed\n";
