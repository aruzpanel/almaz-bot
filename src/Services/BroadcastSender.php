<?php

declare(strict_types=1);

namespace Bot\Services;

use Bot\Repository\BroadcastRepository;
use Bot\Repository\UserRepository;
use Bot\TelegramClient;

/**
 * Sends queued broadcasts outside the webhook request.
 *
 * Telegram allows ~30 messages per second, so users are sent to in parallel batches
 * of BATCH, at most one batch per second. Progress is saved after every batch; if the
 * process dies, the next run() continues from the last saved user id. A file lock makes
 * sure only one process sends at a time.
 */
final class BroadcastSender
{
    private const BATCH = 25;
    private const MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly TelegramClient $telegram,
        private readonly BroadcastRepository $broadcasts,
        private readonly UserRepository $users,
        private readonly int $adminId,
        private readonly string $lockPath,
    ) {
    }

    /** Sends every queued broadcast. Returns at once if another process is already sending. */
    public function run(): void
    {
        $lock = fopen($this->lockPath, 'c');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            return;
        }

        try {
            while (($broadcast = $this->broadcasts->nextActive()) !== null) {
                $this->send($broadcast);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function send(array $broadcast): void
    {
        $id = (int) $broadcast['id'];
        $this->broadcasts->markRunning($id);

        $after = (int) $broadcast['last_user_id'];
        while (($ids = $this->users->idsAfter($after, self::BATCH)) !== []) {
            $started = microtime(true);
            [$sent, $failed] = $this->sendBatch($broadcast, $ids);
            $after = end($ids);
            $this->broadcasts->saveProgress($id, $after, $sent, $failed);

            $elapsed = microtime(true) - $started;
            if ($elapsed < 1.0) {
                usleep((int) ((1.0 - $elapsed) * 1_000_000));
            }
        }

        $this->broadcasts->finish($id);
        $done = $this->broadcasts->find($id);
        $this->telegram->sendMessage(
            $this->adminId,
            "<b>Hammaga yuborildi ✅</b>\n\nYetkazildi: <b>{$done['sent']}</b>\nYetkazilmadi: <b>{$done['failed']}</b> (botni bloklaganlar va o'chirilgan akkauntlar)"
        );
    }

    /**
     * @param int[] $ids
     * @return array{0: int, 1: int} [sent, failed]
     */
    private function sendBatch(array $broadcast, array $ids): array
    {
        $sent = 0;
        $pending = $ids;

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS && $pending !== []; $attempt++) {
            $requests = [];
            foreach ($pending as $userId) {
                $requests[$userId] = $this->request($broadcast, $userId);
            }

            $retry = [];
            $wait = 0;
            foreach ($this->telegram->callMulti($requests) as $userId => $result) {
                if ($result?->ok ?? false) {
                    $sent++;
                } elseif ($result === null || ($result->error_code ?? 0) === 429) {
                    // Network error or rate limit: try this user again after a pause.
                    $retry[] = (int) $userId;
                    $wait = max($wait, (int) ($result->parameters->retry_after ?? 1));
                }
                // Anything else (blocked the bot, deleted account) is a final failure.
            }

            $pending = $retry;
            if ($pending !== []) {
                sleep(min($wait, 30));
            }
        }

        return [$sent, count($ids) - $sent];
    }

    /** @return array{method: string, params: array} */
    private function request(array $broadcast, int $userId): array
    {
        if ($broadcast['kind'] === 'forward') {
            return ['method' => 'forwardMessage', 'params' => [
                'chat_id' => $userId,
                'from_chat_id' => (int) $broadcast['from_chat_id'],
                'message_id' => (int) $broadcast['message_id'],
            ]];
        }

        return ['method' => 'sendMessage', 'params' => [
            'chat_id' => $userId,
            'text' => (string) $broadcast['text'],
            'parse_mode' => 'html',
            'disable_web_page_preview' => 'true',
        ]];
    }
}
