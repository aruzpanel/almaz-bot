<?php

declare(strict_types=1);

namespace Bot\Repository;

final class BroadcastRepository
{
    public function __construct(private readonly \PDO $db)
    {
    }

    public function enqueueText(string $text): int
    {
        return $this->create('text', $text, null, null);
    }

    public function enqueueForward(int $fromChatId, int $messageId): int
    {
        return $this->create('forward', null, $fromChatId, $messageId);
    }

    public function hasActive(): bool
    {
        return (bool) $this->db->query("SELECT 1 FROM broadcasts WHERE status IN ('pending','running') LIMIT 1")
            ->fetchColumn();
    }

    /** Oldest broadcast that is not finished yet, or null. */
    public function nextActive(): ?array
    {
        $row = $this->db->query("SELECT * FROM broadcasts WHERE status IN ('pending','running') ORDER BY id LIMIT 1")
            ->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM broadcasts WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function markRunning(int $id): void
    {
        $this->db->prepare("UPDATE broadcasts SET status = 'running' WHERE id = ?")->execute([$id]);
    }

    public function saveProgress(int $id, int $lastUserId, int $sentDelta, int $failedDelta): void
    {
        $this->db->prepare('UPDATE broadcasts SET last_user_id = ?, sent = sent + ?, failed = failed + ? WHERE id = ?')
            ->execute([$lastUserId, $sentDelta, $failedDelta, $id]);
    }

    public function finish(int $id): void
    {
        $this->db->prepare("UPDATE broadcasts SET status = 'done', finished_at = ? WHERE id = ?")
            ->execute([gmdate('Y-m-d H:i:s'), $id]);
    }

    private function create(string $kind, ?string $text, ?int $fromChatId, ?int $messageId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO broadcasts (kind, text, from_chat_id, message_id, status, created_at) VALUES (?,?,?,?,?,?)'
        );
        $stmt->execute([$kind, $text, $fromChatId, $messageId, 'pending', gmdate('Y-m-d H:i:s')]);

        return (int) $this->db->lastInsertId();
    }
}
