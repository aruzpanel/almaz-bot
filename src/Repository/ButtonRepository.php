<?php

declare(strict_types=1);

namespace Bot\Repository;

final class ButtonRepository
{
    public function __construct(private readonly \PDO $db)
    {
    }

    public function get(string $code): ?string
    {
        $stmt = $this->db->prepare('SELECT label FROM buttons WHERE code = ?');
        $stmt->execute([$code]);
        $value = $stmt->fetchColumn();

        return $value !== false ? (string) $value : null;
    }

    public function exists(string $code): bool
    {
        return $this->get($code) !== null;
    }

    public function set(string $code, string $label): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO buttons (code, label) VALUES (?, ?)
             ON CONFLICT(code) DO UPDATE SET label = excluded.label'
        );
        $stmt->execute([$code, $label]);
    }

    /** @return array<string,string> */
    public function all(): array
    {
        return $this->db->query('SELECT code, label FROM buttons')->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
