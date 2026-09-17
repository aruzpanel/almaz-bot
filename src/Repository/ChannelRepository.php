<?php

declare(strict_types=1);

namespace Bot\Repository;

final class ChannelRepository
{
    public function __construct(private readonly \PDO $db)
    {
    }

    /** @return string[] usernames without leading @ */
    public function all(): array
    {
        return $this->db->query('SELECT username FROM channels ORDER BY id')->fetchAll(\PDO::FETCH_COLUMN);
    }

    /** @return array<int, array{username: string, title: ?string}> */
    public function allWithTitles(): array
    {
        return $this->db->query('SELECT username, title FROM channels ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function add(string $username, ?string $title = null): void
    {
        $stmt = $this->db->prepare('INSERT OR IGNORE INTO channels (username, title) VALUES (?, ?)');
        $stmt->execute([ltrim(trim($username), '@'), $title]);
    }

    public function updateTitle(string $username, string $title): void
    {
        $stmt = $this->db->prepare('UPDATE channels SET title = ? WHERE username = ?');
        $stmt->execute([$title, $username]);
    }

    public function clear(): void
    {
        $this->db->exec('DELETE FROM channels');
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM channels')->fetchColumn();
    }
}
