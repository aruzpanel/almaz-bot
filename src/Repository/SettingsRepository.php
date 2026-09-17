<?php

declare(strict_types=1);

namespace Bot\Repository;

final class SettingsRepository
{
    public function __construct(private readonly \PDO $db)
    {
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = $this->db->prepare('SELECT value FROM settings WHERE key = ?');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();

        return $value !== false ? (string) $value : $default;
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO settings (key, value) VALUES (?, ?)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value'
        );
        $stmt->execute([$key, $value]);
    }

    /** @return array<string,string> */
    public function all(): array
    {
        return $this->db->query('SELECT key, value FROM settings')->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
