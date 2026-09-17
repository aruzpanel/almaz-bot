<?php

declare(strict_types=1);

namespace Bot;

use Bot\Handlers\AdminBroadcast;
use Bot\Handlers\AdminChannels;
use Bot\Handlers\AdminContentEditor;
use Bot\Handlers\AdminPanel;
use Bot\Handlers\AdminPaymentMethods;
use Bot\Handlers\AdminSettings;
use Bot\Handlers\AdminUsers;
use Bot\Handlers\MainMenu;
use Bot\Handlers\Onboarding;
use Bot\Handlers\Withdrawal;
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

final class Router
{
    public function __construct(
        private readonly Config $config,
        private readonly TelegramClient $telegram,
        private readonly UserRepository $users,
        private readonly SettingsRepository $settings,
        private readonly ChannelRepository $channels,
        private readonly PaymentMethodRepository $paymentMethods,
        private readonly TemplateRepository $templateRepo,
        private readonly ButtonRepository $buttonRepo,
        private readonly WithdrawalRepository $withdrawals,
        private readonly Templates $templates,
        private readonly SubscriptionGate $gate,
        private readonly ReferralService $referrals,
        private readonly string $botUsername,
    ) {
    }

    public function dispatch(Update $update): void
    {
        if ($update->type === 'unknown' || $update->fromId === null) {
            return;
        }

        $userExistedBefore = $this->users->exists($update->fromId);
        $user = $this->users->findOrCreate($update->fromId, $update->firstName, $update->lastName, $update->username);
        $isAdmin = $update->fromId === $this->config->adminId;

        if (!$userExistedBefore) {
            $this->telegram->sendMessage($this->config->adminId, "<b>👤 Yangi obunachi botga qo'shildi!</b>");
        }

        $ctx = new Context(
            update: $update,
            user: $user,
            isAdmin: $isAdmin,
            telegram: $this->telegram,
            users: $this->users,
            settings: $this->settings,
            channels: $this->channels,
            paymentMethods: $this->paymentMethods,
            templateRepo: $this->templateRepo,
            buttonRepo: $this->buttonRepo,
            withdrawals: $this->withdrawals,
            templates: $this->templates,
            gate: $this->gate,
            referrals: $this->referrals,
            config: $this->config,
            labels: new Labels($this->buttonRepo),
            botUsername: $this->botUsername,
        );

        if ($update->type === 'message') {
            $this->routeMessage($ctx, $userExistedBefore);
        } elseif ($update->type === 'callback_query') {
            $this->routeCallback($ctx);
        }
    }

    private function routeMessage(Context $ctx, bool $userExistedBefore): void
    {
        if ($ctx->user->state !== null && $this->dispatchState($ctx)) {
            return;
        }

        $text = $ctx->update->text;
        if ($text === null) {
            return;
        }

        if ($text === '/start') {
            Onboarding::handleStartCommand($ctx);
            return;
        }

        if (str_starts_with($text, '/start ')) {
            $refId = trim(explode(' ', $text, 2)[1] ?? '');
            if ($refId !== '' && is_numeric($refId) && (int) $refId > 0) {
                Onboarding::handleReferralStart($ctx, (int) $refId, $userExistedBefore);
            }
            return;
        }

        if ($text === '/panel' && $ctx->isAdmin) {
            AdminPanel::handleOpen($ctx);
            return;
        }

        if ($text === '🗄 Boshqarish' && $ctx->isAdmin) {
            AdminPanel::handleOpen($ctx);
            return;
        }

        if ($text === '💎ALMAZ ISHLASH 💎') {
            MainMenu::handleEarnMenu($ctx);
            return;
        }

        $userRoutes = [
            $ctx->labels->earn => fn () => MainMenu::handleEarn($ctx),
            $ctx->labels->cabinet => fn () => MainMenu::handleCabinet($ctx),
            $ctx->labels->tolov => fn () => MainMenu::handlePaymentsChannel($ctx),
            $ctx->labels->manual => fn () => MainMenu::handleManual($ctx),
            $ctx->labels->support => fn () => MainMenu::handleSupportEntry($ctx),
            $ctx->labels->solve => fn () => Withdrawal::handleSolveEntry($ctx),
            $ctx->labels->back => fn () => MainMenu::handleBackToHome($ctx),
            '⚙️PULLI NASTROYKA ⚙️' => fn () => MainMenu::handlePaidSetup($ctx),
        ];

        if (isset($userRoutes[$text])) {
            ($userRoutes[$text])();
            return;
        }

        if (!$ctx->isAdmin) {
            return;
        }

        $adminRoutes = [
            '⚙ Asosiy sozlamalar' => fn () => AdminSettings::handleMenu($ctx),
            '📢 Kanallar' => fn () => AdminChannels::handleMenu($ctx),
            '📊 Statistika' => fn () => AdminPanel::handleStatistika($ctx),
            '🔎 Foydalanuvchini boshqarish' => fn () => AdminUsers::handleMenuEntry($ctx),
            '🎛 Tugmalar' => fn () => AdminContentEditor::handleButtonMenuEntry($ctx),
            '📃 Matnlar' => fn () => AdminContentEditor::handleTemplateMenuEntry($ctx),
            "💳 To'lov tizimi" => fn () => AdminPaymentMethods::handleMenu($ctx),
            '📨 Xabarnoma' => fn () => AdminBroadcast::handleMenu($ctx),
        ];

        if (isset($adminRoutes[$text])) {
            ($adminRoutes[$text])();
        }
    }

    private function dispatchState(Context $ctx): bool
    {
        $state = (string) $ctx->user->state;

        $common = [
            'request_contact' => fn () => Onboarding::handleContact($ctx),
            'withdraw_wallet' => fn () => Withdrawal::handleWalletStep($ctx),
            'withdraw_amount' => fn () => Withdrawal::handleAmountStep($ctx),
            'yordam' => fn () => MainMenu::handleSupportMessage($ctx),
        ];

        if (isset($common[$state])) {
            ($common[$state])();
            return true;
        }

        if (!$ctx->isAdmin) {
            return false;
        }

        $admin = [
            'settings_taklif' => fn () => AdminSettings::handleTaklifStep($ctx),
            'settings_valyuta' => fn () => AdminSettings::handleValyutaStep($ctx),
            'settings_narx' => fn () => AdminSettings::handleNarxStep($ctx),
            'settings_admin_user' => fn () => AdminSettings::handleAdminUserStep($ctx),
            'channel_add' => fn () => AdminChannels::handleQoshishStep($ctx),
            'payments_channel' => fn () => AdminChannels::handleVazifaStep($ctx),
            'payment_method_add' => fn () => AdminPaymentMethods::handleNewStep($ctx),
            'user_lookup' => fn () => AdminUsers::handleLookupStep($ctx),
            'balance_plus' => fn () => AdminUsers::handlePlusStep($ctx),
            'balance_minus' => fn () => AdminUsers::handleMinusStep($ctx),
            'broadcast_target' => fn () => AdminBroadcast::handleUserIdStep($ctx),
            'broadcast_message' => fn () => AdminBroadcast::handleMessageStep($ctx),
            'broadcast_all' => fn () => AdminBroadcast::handleSendStep($ctx),
            'broadcast_all_forward' => fn () => AdminBroadcast::handleForsendStep($ctx),
            'button_code' => fn () => AdminContentEditor::handleButtonCodeStep($ctx),
            'button_value' => fn () => AdminContentEditor::handleButtonValueStep($ctx),
            'template_code' => fn () => AdminContentEditor::handleTemplateCodeStep($ctx),
            'template_value' => fn () => AdminContentEditor::handleTemplateValueStep($ctx),
        ];

        if (isset($admin[$state])) {
            ($admin[$state])();
            return true;
        }

        return false;
    }

    private function routeCallback(Context $ctx): void
    {
        $data = $ctx->update->data;
        if ($data === null) {
            return;
        }

        $shared = [
            'check' => fn () => Onboarding::handleCheckCallback($ctx),
            'davom' => fn () => Onboarding::handleContinueCallback($ctx),
            'yechish' => fn () => Withdrawal::handleYechishCallback($ctx),
            'tasdiq' => fn () => Withdrawal::handleConfirm($ctx),
            'bekor' => fn () => Withdrawal::handleCancel($ctx),
            'yopish' => fn () => AdminPanel::handleClose($ctx),
            'top' => fn () => MainMenu::handleTopReferrals($ctx),
        ];

        if (isset($shared[$data])) {
            ($shared[$data])();
            return;
        }

        if (str_starts_with($data, 'pay-')) {
            Withdrawal::handlePaySelected($ctx, substr($data, 4));
            return;
        }

        if (!$ctx->isAdmin) {
            return;
        }

        $adminRoutes = [
            'asosiy' => fn () => AdminSettings::handleMenuCallback($ctx),
            'holat' => fn () => AdminSettings::handleHolat($ctx),
            'taklif' => fn () => AdminSettings::handleTaklifEntry($ctx),
            'valyuta' => fn () => AdminSettings::handleValyutaEntry($ctx),
            'narx' => fn () => AdminSettings::handleNarxEntry($ctx),
            'admin' => fn () => AdminSettings::handleAdminUserEntry($ctx),
            'kanallar' => fn () => AdminChannels::handleMenuCallback($ctx),
            'majburiy' => fn () => AdminChannels::handleMajburiy($ctx),
            'qoshish' => fn () => AdminChannels::handleQoshishEntry($ctx),
            'ochirish' => fn () => AdminChannels::handleOchirish($ctx),
            'royxat' => fn () => AdminChannels::handleRoyxat($ctx),
            'qoshimcha' => fn () => AdminChannels::handleQoshimcha($ctx),
            'vazifa' => fn () => AdminChannels::handleVazifaEntry($ctx),
            'hamyon' => fn () => AdminPaymentMethods::handleMenuCallback($ctx),
            'new' => fn () => AdminPaymentMethods::handleNewEntry($ctx),
            'foydalanuvchi' => fn () => AdminUsers::handleFoydalanuvchiCallback($ctx),
            'plus' => fn () => AdminUsers::handlePlusEntry($ctx),
            'minus' => fn () => AdminUsers::handleMinusEntry($ctx),
            'ban' => fn () => AdminUsers::handleBanToggle($ctx),
            'user' => fn () => AdminBroadcast::handleUserEntry($ctx),
            'send' => fn () => AdminBroadcast::handleSendEntry($ctx),
            'forsend' => fn () => AdminBroadcast::handleForsendEntry($ctx),
        ];

        if (isset($adminRoutes[$data])) {
            ($adminRoutes[$data])();
            return;
        }

        if (str_starts_with($data, 'del-')) {
            AdminPaymentMethods::handleDelete($ctx, substr($data, 4));
            return;
        }

        if (preg_match('/^wd:(paid|unpaid|ban):(\d+)$/', $data, $m) === 1) {
            Withdrawal::handleAdminResolve($ctx, $m[1], (int) $m[2]);
        }
    }
}
