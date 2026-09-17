<?php

declare(strict_types=1);

namespace Bot;

final class Database
{
    private static ?\PDO $instance = null;

    public static function connect(string $path): \PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $pdo = new \PDO('sqlite:' . $path);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');

        self::migrate($pdo);
        self::seedDefaults($pdo);

        self::$instance = $pdo;

        return $pdo;
    }

    private static function migrate(\PDO $pdo): void
    {
        $schema = file_get_contents(__DIR__ . '/../schema.sql');
        if ($schema === false) {
            throw new \RuntimeException('schema.sql not found');
        }
        $pdo->exec($schema);
    }

    private static function seedDefaults(\PDO $pdo): void
    {
        $insertSetting = $pdo->prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)');
        foreach (Defaults::settings() as $key => $value) {
            $insertSetting->execute([$key, $value]);
        }

        $insertTemplate = $pdo->prepare('INSERT OR IGNORE INTO templates (code, content) VALUES (?, ?)');
        foreach (Defaults::templates() as $code => $content) {
            $insertTemplate->execute([$code, $content]);
        }

        $insertButton = $pdo->prepare('INSERT OR IGNORE INTO buttons (code, label) VALUES (?, ?)');
        foreach (Defaults::buttons() as $code => $label) {
            $insertButton->execute([$code, $label]);
        }
    }
}
