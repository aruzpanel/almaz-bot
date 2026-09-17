<?php

declare(strict_types=1);

namespace Bot;

final class Config
{
    public readonly string $botToken;
    public readonly int $adminId;
    public readonly string $webhookSecret;
    public readonly string $dbPath;
    public readonly string $botUsername;
    /** @var string[] */
    public readonly array $allowedPhonePrefixes;

    public function __construct()
    {
        $this->botToken = self::env('BOT_TOKEN');
        $this->adminId = (int) self::env('ADMIN_ID');
        $this->webhookSecret = self::env('WEBHOOK_SECRET', '');
        $this->dbPath = self::env('DB_PATH', __DIR__ . '/../data/bot.sqlite');
        $this->botUsername = self::env('BOT_USERNAME', '');
        $this->allowedPhonePrefixes = array_values(array_filter(array_map(
            'trim',
            explode(',', self::env('ALLOWED_PHONE_PREFIXES', '998,996'))
        )));
    }

    private static function env(string $key, ?string $default = null): string
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            if ($default !== null) {
                return $default;
            }
            throw new \RuntimeException("Missing required env var: $key");
        }

        return (string) $value;
    }
}
