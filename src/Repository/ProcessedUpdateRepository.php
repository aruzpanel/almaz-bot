<?php

declare(strict_types=1);

namespace Bot\Repository;

final class ProcessedUpdateRepository
{
    private const KEEP_SECONDS = 2 * 86400;

    public function __construct(private readonly \PDO $db)
    {
    }

    /** Returns false when this update_id was already handled (a Telegram retry). */
    public function claim(int $updateId): bool
    {
        $stmt = $this->db->prepare('INSERT OR IGNORE INTO processed_updates (update_id, received_at) VALUES (?, ?)');
        $stmt->execute([$updateId, time()]);
        $claimed = $stmt->rowCount() === 1;

        if ($claimed && $updateId % 500 === 0) {
            $this->db->prepare('DELETE FROM processed_updates WHERE received_at < ?')
                ->execute([time() - self::KEEP_SECONDS]);
        }

        return $claimed;
    }
}
