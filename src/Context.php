<?php

declare(strict_types=1);

namespace Bot;

use Bot\Entity\User;
use Bot\Repository\ButtonRepository;
use Bot\Repository\ChannelRepository;
use Bot\Repository\PaymentMethodRepository;
use Bot\Repository\SettingsRepository;
use Bot\Repository\TemplateRepository;
use Bot\Repository\UserRepository;
use Bot\Repository\WithdrawalRepository;
use Bot\Services\ReferralService;
use Bot\Services\SubscriptionGate;
use Bot\Support\Labels;
use Bot\Support\Templates;

final class Context
{
    public function __construct(
        public readonly Update $update,
        public readonly User $user,
        public readonly bool $isAdmin,
        public readonly TelegramClient $telegram,
        public readonly UserRepository $users,
        public readonly SettingsRepository $settings,
        public readonly ChannelRepository $channels,
        public readonly PaymentMethodRepository $paymentMethods,
        public readonly TemplateRepository $templateRepo,
        public readonly ButtonRepository $buttonRepo,
        public readonly WithdrawalRepository $withdrawals,
        public readonly Templates $templates,
        public readonly SubscriptionGate $gate,
        public readonly ReferralService $referrals,
        public readonly Config $config,
        public readonly Labels $labels,
        public readonly string $botUsername,
    ) {
    }

    /** Common placeholders shared by almost every rendered template. */
    public function baseVars(): array
    {
        return [
            'first' => $this->user->firstName ?? '',
            'last' => $this->user->lastName ?? '',
            'id' => $this->user->id,
            'hour' => date('H:i'),
            'date' => date('d.m.Y'),
        ];
    }

    public function setState(?string $state): void
    {
        $this->user->state = $state;
        $this->users->save($this->user);
    }

    public function setStateValue(string $key, mixed $value): void
    {
        $this->user->stateData[$key] = $value;
        $this->users->save($this->user);
    }

    public function stateValue(string $key, mixed $default = null): mixed
    {
        return $this->user->stateData[$key] ?? $default;
    }

    public function clearStateValue(string $key): void
    {
        unset($this->user->stateData[$key]);
        $this->users->save($this->user);
    }

    public function mainMenuKeyboard(): string
    {
        return \Bot\Support\Menus::main($this->labels, $this->isAdmin);
    }
}
