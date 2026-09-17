<?php

declare(strict_types=1);

namespace Bot\Entity;

final class User
{
    public function __construct(
        public int $id,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $username = null,
        public ?string $phone = null,
        public int $balance = 0,
        public int $withdrawn = 0,
        public int $referralCount = 0,
        public ?int $referredBy = null,
        public bool $isBanned = false,
        public int $joinCheckClicks = 0,
        public ?string $state = null,
        public array $stateData = [],
        public ?string $createdAt = null,
    ) {
    }

    public function hasPhone(): bool
    {
        return $this->phone !== null && $this->phone !== '';
    }
}
