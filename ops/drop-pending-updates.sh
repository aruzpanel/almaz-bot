#!/usr/bin/env bash
# Run on the VPS:  ssh danatpay-vps 'bash -s' < ops/drop-pending-updates.sh
# Drops Telegram's queue of undelivered updates. The webhook URL and secret stay
# the same (read from the server .env, never printed).
set -e
ENV=/var/www/bot.danatpay.uz/.env
TOKEN=$(sudo grep -E "^BOT_TOKEN=" "$ENV" | cut -d= -f2- | tr -d "\"'\r ")
SECRET=$(sudo grep -E "^WEBHOOK_SECRET=" "$ENV" | cut -d= -f2- | tr -d "\"'\r ")
[ -n "$TOKEN" ] && [ -n "$SECRET" ] || { echo "BOT_TOKEN yoki WEBHOOK_SECRET topilmadi"; exit 1; }
API="https://api.telegram.org/bot$TOKEN"
echo "oldin:  $(curl -s "$API/getWebhookInfo" | grep -oE '"pending_update_count":[0-9]+')"
curl -s "$API/setWebhook" \
  --data-urlencode "url=https://bot.danatpay.uz/" \
  --data-urlencode "secret_token=$SECRET" \
  -d "drop_pending_updates=true" | grep -oE '"ok":[a-z]+|"description":"[^"]*"'
sleep 2
echo "keyin:  $(curl -s "$API/getWebhookInfo" | grep -oE '"url":"[^"]*"|"pending_update_count":[0-9]+' | tr '\n' ' ')"
