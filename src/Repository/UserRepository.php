<?php

declare(strict_types=1);

namespace Bot\Repository;

use Bot\Entity\User;

final class UserRepository
{
    public function __construct(private readonly \PDO $db)
    {
    }

    public function find(int $id): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function findOrCreate(int $id, ?string $firstName, ?string $lastName, ?string $username): User
    {
        $user = $this->find($id);
        if ($user !== null) {
            $user->firstName = $firstName;
            $user->lastName = $lastName;
            $user->username = $username;
            $this->save($user);

            return $user;
        }

        $user = new User($id, $firstName, $lastName, $username);
        $this->insert($user);

        return $user;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetchColumn() !== false;
    }

    private function insert(User $u): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (id, first_name, last_name, username, phone, balance, withdrawn,
                referral_count, referred_by, is_banned, join_check_clicks, state, state_data, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $u->id, $u->firstName, $u->lastName, $u->username, $u->phone,
            $u->balance, $u->withdrawn, $u->referralCount, $u->referredBy,
            (int) $u->isBanned, $u->joinCheckClicks, $u->state,
            json_encode($u->stateData, JSON_UNESCAPED_UNICODE), gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function save(User $u): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET first_name=?, last_name=?, username=?, phone=?, balance=?, withdrawn=?,
                referral_count=?, referred_by=?, is_banned=?, join_check_clicks=?, state=?, state_data=?
             WHERE id=?'
        );
        $stmt->execute([
            $u->firstName, $u->lastName, $u->username, $u->phone,
            $u->balance, $u->withdrawn, $u->referralCount, $u->referredBy,
            (int) $u->isBanned, $u->joinCheckClicks, $u->state,
            json_encode($u->stateData, JSON_UNESCAPED_UNICODE), $u->id,
        ]);
    }

    public function setState(int $id, ?string $state): void
    {
        $stmt = $this->db->prepare('UPDATE users SET state = ? WHERE id = ?');
        $stmt->execute([$state, $id]);
    }

    public function adjustBalance(int $id, int $delta): void
    {
        $stmt = $this->db->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
        $stmt->execute([$delta, $id]);
    }

    public function adjustWithdrawn(int $id, int $delta): void
    {
        $stmt = $this->db->prepare('UPDATE users SET withdrawn = withdrawn + ? WHERE id = ?');
        $stmt->execute([$delta, $id]);
    }

    public function incrementReferralCount(int $id): void
    {
        $this->db->prepare('UPDATE users SET referral_count = referral_count + 1 WHERE id = ?')->execute([$id]);
    }

    public function incrementJoinCheckClicks(int $id): void
    {
        $this->db->prepare('UPDATE users SET join_check_clicks = join_check_clicks + 1 WHERE id = ?')->execute([$id]);
    }

    public function getGateOkUntil(int $id): ?int
    {
        $stmt = $this->db->prepare('SELECT gate_ok_until FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $value = $stmt->fetchColumn();

        return ($value !== false && $value !== null) ? (int) $value : null;
    }

    public function setGateOkUntil(int $id, int $timestamp): void
    {
        $this->db->prepare('UPDATE users SET gate_ok_until = ? WHERE id = ?')->execute([$timestamp, $id]);
    }

    public function setBanned(int $id, bool $banned): void
    {
        $this->db->prepare('UPDATE users SET is_banned = ? WHERE id = ?')->execute([(int) $banned, $id]);
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    /** @return int[] */
    public function allIds(): array
    {
        return array_map('intval', $this->db->query('SELECT id FROM users')->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** @return int[] up to $limit user ids greater than $afterId, ascending */
    public function idsAfter(int $afterId, int $limit): array
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE id > ? ORDER BY id LIMIT ?');
        $stmt->execute([$afterId, $limit]);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** @return array<int, array{id: int, first_name: ?string, referral_count: int}> */
    public function topReferrers(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, first_name, referral_count FROM users
             WHERE referral_count > 0
             ORDER BY referral_count DESC, id ASC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countCreatedSince(string $sinceDatetime): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE created_at >= ?');
        $stmt->execute([$sinceDatetime]);

        return (int) $stmt->fetchColumn();
    }

    public function sumBalance(): int
    {
        return (int) $this->db->query('SELECT COALESCE(SUM(balance), 0) FROM users')->fetchColumn();
    }

    public function sumWithdrawn(): int
    {
        return (int) $this->db->query('SELECT COALESCE(SUM(withdrawn), 0) FROM users')->fetchColumn();
    }

    public function countBanned(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM users WHERE is_banned = 1')->fetchColumn();
    }

    private function hydrate(array $row): User
    {
        return new User(
            id: (int) $row['id'],
            firstName: $row['first_name'],
            lastName: $row['last_name'],
            username: $row['username'],
            phone: $row['phone'],
            balance: (int) $row['balance'],
            withdrawn: (int) $row['withdrawn'],
            referralCount: (int) $row['referral_count'],
            referredBy: $row['referred_by'] !== null ? (int) $row['referred_by'] : null,
            isBanned: (bool) $row['is_banned'],
            joinCheckClicks: (int) $row['join_check_clicks'],
            state: $row['state'],
            stateData: $row['state_data'] ? (json_decode((string) $row['state_data'], true) ?: []) : [],
            createdAt: $row['created_at'],
        );
    }
}
