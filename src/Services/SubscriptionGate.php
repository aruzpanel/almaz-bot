<?php

declare(strict_types=1);

namespace Bot\Services;

use Bot\Repository\ChannelRepository;
use Bot\Repository\UserRepository;
use Bot\Support\Keyboards;
use Bot\TelegramClient;

final class SubscriptionGate
{
    private const NUDGE_TEXT = "<b>❗️ Botdan foydalanish uchun kanallarga obuna bo'ling.</b>";
    private const FULL_TEXT = "<b>⚠️ Botdan to'liq foydalanish uchun barcha kanallarga obuna bo'ling.</b>";

    /** How long a confirmed subscription is trusted before re-checking, in seconds. */
    private const CACHE_TTL = 300;

    public function __construct(
        private readonly TelegramClient $telegram,
        private readonly ChannelRepository $channels,
        private readonly UserRepository $users,
    ) {
    }

    /**
     * Mirrors the legacy joinchat(): sends the gate message itself when the
     * user has not (yet) satisfied the subscription requirement, and returns
     * whether the caller may proceed.
     *
     * All Telegram API calls needed to render the gate (membership checks,
     * plus a channel-title lookup the first time a channel is seen) are
     * fired in a single parallel batch instead of one-by-one, and titles are
     * cached afterwards so later checks only need the membership calls.
     */
    public function check(int $userId, string $checkButtonLabel = '🔄 Tekshirish'): bool
    {
        $cachedUntil = $this->users->getGateOkUntil($userId);
        if ($cachedUntil !== null && $cachedUntil > time()) {
            return true;
        }

        $channels = $this->channels->allWithTitles();

        if ($channels === []) {
            return true;
        }

        $requests = [];
        foreach ($channels as $i => $channel) {
            $requests["member:$i"] = [
                'method' => 'getChatMember',
                'params' => ['chat_id' => '@' . $channel['username'], 'user_id' => $userId],
            ];
            if ($channel['title'] === null || $channel['title'] === '') {
                $requests["chat:$i"] = [
                    'method' => 'getChat',
                    'params' => ['chat_id' => '@' . $channel['username']],
                ];
            }
        }

        $results = $this->telegram->callMulti($requests);

        $rows = [];
        $allSubscribed = true;

        foreach ($channels as $i => $channel) {
            $username = $channel['username'];
            $title = $channel['title'];

            if (isset($results["chat:$i"])) {
                $chat = $results["chat:$i"];
                if ($chat && ($chat->ok ?? false)) {
                    $title = $chat->result->title;
                    $this->channels->updateTitle($username, $title);
                }
            }
            $title ??= $username;

            $member = $results["member:$i"] ?? null;
            $status = ($member && ($member->ok ?? false)) ? $member->result->status : null;
            $subscribed = in_array($status, ['creator', 'administrator', 'member'], true);

            if (!$subscribed) {
                $allSubscribed = false;
            }

            $rows[] = [Keyboards::button(($subscribed ? '✅ ' : '❌ ') . $title, ['url' => "https://t.me/$username"])];
        }

        $rows[] = [Keyboards::button($checkButtonLabel, ['callback_data' => 'check'])];

        $this->users->incrementJoinCheckClicks($userId);
        $clicks = $this->users->find($userId)?->joinCheckClicks ?? 1;

        if ($clicks < 3) {
            $this->telegram->sendMessage($userId, self::NUDGE_TEXT, [
                'disable_web_page_preview' => true,
                'reply_markup' => Keyboards::inline($rows),
            ]);

            return false;
        }

        if (!$allSubscribed) {
            $this->telegram->sendMessage($userId, self::FULL_TEXT, [
                'disable_web_page_preview' => true,
                'reply_markup' => Keyboards::inline($rows),
            ]);

            return false;
        }

        $this->users->setGateOkUntil($userId, time() + self::CACHE_TTL);

        return true;
    }
}
