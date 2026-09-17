<?php

declare(strict_types=1);

namespace Bot\Repository;

final class TemplateRepository
{
    public function __construct(private readonly \PDO $db)
    {
    }

    public function get(string $code): ?string
    {
        $stmt = $this->db->prepare('SELECT content FROM templates WHERE code = ?');
        $stmt->execute([$code]);
        $value = $stmt->fetchColumn();

        return $value !== false ? (string) $value : null;
    }

    public function exists(string $code): bool
    {
        return $this->get($code) !== null;
    }

    public function set(string $code, string $content): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO templates (code, content) VALUES (?, ?)
             ON CONFLICT(code) DO UPDATE SET content = excluded.content'
        );
        $stmt->execute([$code, $content]);
    }
}
