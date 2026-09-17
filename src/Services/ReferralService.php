<?php

declare(strict_types=1);

namespace Bot\Services;

use Bot\Repository\SettingsRepository;
use Bot\Repository\UserRepository;
use Bot\Support\Templates;
use Bot\TelegramClient;

final class ReferralService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly SettingsRepository $settings,
        private readonly TelegramClient $telegram,
        private readonly Templates $templates,
    ) {
    }

    public function registerPending(int $userId, int $referrerId): void
    {
        $user = $this->users->find($userId);
        if ($user === null) {
            return;
        }
        $user->stateData['pending_referrer_id'] = $referrerId;
        $this->users->save($user);
    }

    public function getPendingReferrerId(int $userId): ?int
    {
        $user = $this->users->find($userId);

        return $user?->stateData['pending_referrer_id'] ?? null;
    }

    public function clearPending(int $userId): void
    {
        $user = $this->users->find($userId);
        if ($user === null) {
            return;
        }
        unset($user->stateData['pending_referrer_id']);
        $this->users->save($user);
    }

    /**
     * Credits the referral bonus to the pending referrer of $userId, if any.
     * Safe to call more than once: the pending marker is cleared immediately.
     */
    public function creditIfPending(int $userId): void
    {
        $referrerId = $this->getPendingReferrerId($userId);
        if ($referrerId === null) {
            return;
        }

        $this->clearPending($userId);

        $bonus = (int) $this->settings->get('taklif', '250');
        $this->users->adjustBalance($referrerId, $bonus);
        $this->users->incrementReferralCount($referrerId);

        $currency = $this->settings->get('valyuta', "so'm");
        $this->telegram->sendMessage($referrerId, $this->templates->render('checkRef', [
            'refpay' => $bonus,
            'currency' => $currency,
        ]));
    }
}
