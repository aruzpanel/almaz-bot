<?php

declare(strict_types=1);

namespace Bot\Repository;

final class PaymentMethodRepository
{
    public function __construct(private readonly \PDO $db)
    {
    }

    /** @return string[] */
    public function all(): array
    {
        return $this->db->query('SELECT name FROM payment_methods ORDER BY id')->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function add(string $name): void
    {
        $stmt = $this->db->prepare('INSERT OR IGNORE INTO payment_methods (name) VALUES (?)');
        $stmt->execute([trim($name)]);
    }

    public function remove(string $name): void
    {
        $stmt = $this->db->prepare('DELETE FROM payment_methods WHERE name = ?');
        $stmt->execute([$name]);
    }

    public function exists(string $name): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM payment_methods WHERE name = ?');
        $stmt->execute([$name]);

        return $stmt->fetchColumn() !== false;
    }
}
