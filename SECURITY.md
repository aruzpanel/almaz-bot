# Security policy

## Reporting a vulnerability

Please do **not** open a public issue for security problems.

Report it privately through GitHub: open the repository's **Security** tab and choose **Report a vulnerability**. Include the steps to reproduce it and what an attacker could do.

You should get a reply within 7 days. Once a fix is released, we will credit you, unless you prefer to stay anonymous.

## Scope

Things we especially want to hear about:

- getting around the webhook secret or the Mini App `initData` check;
- changing balances, or approving or creating payouts without being the admin;
- reaching admin panel functions as a normal user;
- injecting HTML or markup into messages sent by the bot.

## Running your own copy safely

- Keep `.env` and `data/` outside the web root. Only `public/` should be served.
- Always set `WEBHOOK_SECRET`.
- If a bot token leaks, revoke it in @BotFather right away.
