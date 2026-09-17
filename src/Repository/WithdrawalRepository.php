<?php

declare(strict_types=1);

namespace Bot\Repository;

final class WithdrawalRepository
{
    public function __construct(private readonly \PDO $db)
    {
    }

    public function create(int $userId, string $paymentMethod, string $gameId, int $amount): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO withdrawals (user_id, payment_method, game_id, amount, status, created_at)
             VALUES (?,?,?,?,?,?)'
        );
        $stmt->execute([$userId, $paymentMethod, $gameId, $amount, 'pending', gmdate('Y-m-d H:i:s')]);

        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM withdrawals WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function markResolved(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE withdrawals SET status = ?, resolved_at = ? WHERE id = ?');
        $stmt->execute([$status, gmdate('Y-m-d H:i:s'), $id]);
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM withdrawals WHERE status = ?');
        $stmt->execute([$status]);

        return (int) $stmt->fetchColumn();
    }

    public function sumAmountByStatus(string $status): int
    {
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE status = ?');
        $stmt->execute([$status]);

        return (int) $stmt->fetchColumn();
    }
}
