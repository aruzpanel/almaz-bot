# Contributing

Thanks for helping. Issues and pull requests are welcome, in English or Uzbek.

## Before you start

- For anything bigger than a small fix, open an issue first so we can agree on the approach.
- Never commit a real `.env`, bot token, database file (`data/`, `*.sqlite`) or user data. Use a separate test bot.

## Development

1. Copy `.env.example` to `.env` and fill it in with your **test** bot's values.
2. Run `php -S localhost:8000 -t public` and expose it with a tunnel (for example `ngrok http 8000`).
3. Point your test bot's webhook at the tunnel (see the README).

## Code style

- PHP 8.1+, `declare(strict_types=1);` in every file, no external dependencies.
- New menu sections go into their own class in `src/Handlers/` and are wired up in `src/Router.php`.
- Database access goes through a class in `src/Repository/`. Schema changes go into `schema.sql` and must work on an existing database (`CREATE ... IF NOT EXISTS`, additive columns).
- User-facing text belongs in `src/Defaults.php` so admins can edit it from the bot.

## Pull requests

- Keep each PR to one change and describe how you tested it with a test bot.
- Run `find src public -name "*.php" -exec php -l {} \;` before pushing.
