# Almaz-bot

A Telegram referral bot written in plain PHP 8 with SQLite. Users invite friends, earn a balance for each referral, and ask for a payout to their in-game ID. An admin approves each payout from inside Telegram. Everything else is also managed from Telegram: settings, required channels, payment methods, texts, buttons and broadcasts.

It is a full rewrite of a common single-file Telegram bot template. The template kept data in hundreds of small text files and handled everything in about 250 `if` blocks. This version has a router, one handler class per section, a real database and a Telegram Mini App. It needs no framework and no Composer packages.

O'zbekcha: [README.uz.md](README.uz.md)

## Features

- **Onboarding:** phone number check, limited to allowed country codes. Users must join the required channels before they can use the bot.
- **Referrals:** each user gets an invite link and earns a configurable bonus for every new user who joins through it.
- **Payouts:** the user picks a payment method, then enters their game ID and the amount. The admin approves or rejects the request with one button. Every request is stored in `withdrawals` as an audit trail.
- **Admin panel in Telegram:** settings (bonus, currency, minimum payout), required channels, payment methods, statistics, ban/unban and balance edits, and editing every message text and button label.
- **Broadcasts:** sends to all users in the background at about 25 messages per second. The webhook replies to Telegram at once, so Telegram does not time out and resend. Interrupted broadcasts resume from where they stopped.
- **Duplicate update guard:** each `update_id` is stored, so Telegram retries are ignored.
- **Mini App** (`public/app/`): Cabinet, Invite and Withdraw tabs. Requests are checked against Telegram `initData` using HMAC.
- **Security:** the token and admin ID live in `.env`, never in the code. Webhook requests are verified with `X-Telegram-Bot-Api-Secret-Token`. User text is HTML-escaped and balance changes are atomic SQL updates.

## Requirements

- PHP 8.1+ with `pdo_sqlite` and `curl`
- A web server with HTTPS pointing at `public/` (PHP-FPM is recommended: broadcasts use `fastcgi_finish_request`)
- A bot token from [@BotFather](https://t.me/BotFather)

## Setup

```bash
git clone https://github.com/aruzpanel/almaz-bot.git
cd almaz-bot
cp .env.example .env      # then fill it in, see below
mkdir -p data             # must be writable by the web server user
```

The database schema (`schema.sql`) is applied automatically on first run.

Point the bot's webhook at your server. Use the same secret as `WEBHOOK_SECRET`:

```bash
curl "https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://your-domain/&secret_token=<WEBHOOK_SECRET>"
```

Optional cron job that resumes interrupted broadcasts (run as the web server user):

```cron
* * * * * php /path/to/almaz-bot/broadcast_worker.php
```

## Configuration

| Variable | Required | Description |
|---|---|---|
| `BOT_TOKEN` | yes | Token from @BotFather |
| `ADMIN_ID` | yes | Telegram user ID of the main admin |
| `WEBHOOK_SECRET` | recommended | Long random string, must match `secret_token` in `setWebhook` |
| `BOT_USERNAME` | yes | Bot username without `@`, used in referral links |
| `DB_PATH` | no | SQLite file, default `data/bot.sqlite` |
| `ALLOWED_PHONE_PREFIXES` | no | Comma-separated country codes, default `998,996` |
| `TELEGRAM_API_BASE` | no | Only for tests or a self-hosted Bot API server |

Bonus amounts, currency name, channels, payment methods, texts and buttons are set from the admin panel, not in `.env`.

**Before running your own copy:** a few links still point at the original deployment. These are the support account, the shop button and the Mini App URL in `src/Handlers/MainMenu.php`, and the default "manual" text in `src/Defaults.php`. Change them to your own.

## Local testing

Use a separate test bot, never your production token.

```bash
find src public -name "*.php" -exec php -l {} \;   # syntax check
php -S localhost:8000 -t public
ngrok http 8000                                     # or any tunnel, then setWebhook to it
```

Then try `/start`, an invite link (`/start <user-id>`), sharing a phone number, the channel check, the cabinet, a full payout request, and every admin section.

## Migrating from the old file-based bot

```bash
php migrate_from_legacy.php /path/to/old-bot-folder
```

This imports balances, users, bans, settings, channels, and edited texts and buttons from the old `pul/`, `odam/`, `ban/`, `admin/*.txt` files. It is safe to run more than once.

## Project layout

```
public/index.php         webhook entry point (web root = public/)
public/app/              Telegram Mini App and its JSON API
bootstrap.php            config, autoloader and wiring
schema.sql               database schema
broadcast_worker.php     cron safety net for broadcasts
migrate_from_legacy.php  one-time import from the old bot
src/
  Router.php             maps buttons and user state to handlers
  Handlers/              one class per menu section (user and admin)
  Repository/            one class per table
  Services/              subscription gate, referrals, broadcast sender
  Support/               keyboards, texts, labels, menus
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Security issues: see [SECURITY.md](SECURITY.md).

## License

[MIT](LICENSE)
