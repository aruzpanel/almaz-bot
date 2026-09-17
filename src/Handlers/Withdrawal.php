<?php

declare(strict_types=1);

namespace Bot\Handlers;

use Bot\Context;
use Bot\Support\Keyboards;
use Bot\Support\Menus;

final class Withdrawal
{
    private static function payKeyboard(Context $ctx): string
    {
        $rows = [];
        foreach ($ctx->paymentMethods->all() as $name) {
            $rows[] = [Keyboards::button($name, ['callback_data' => 'pay-' . $name])];
        }

        return Keyboards::inline($rows);
    }

    public static function handleSolveEntry(Context $ctx): void
    {
        if (!Onboarding::requirePhone($ctx) || !$ctx->gate->check($ctx->user->id, $ctx->labels->check)) {
            return;
        }

        if ($ctx->paymentMethods->all() === []) {
            $ctx->telegram->sendMessage($ctx->user->id, "<b>To'lov tizimlari topilmadi!</b>");
            return;
        }

        $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('selectPayType', $ctx->baseVars()), [
            'reply_markup' => self::payKeyboard($ctx),
        ]);
    }

    public static function handleYechishCallback(Context $ctx): void
    {
        if ($ctx->paymentMethods->all() === []) {
            $ctx->telegram->answerCallbackQuery($ctx->update->callbackId, [
                'text' => "To'lov tizimlari topilmadi!",
                'show_alert' => true,
            ]);
            return;
        }

        $ctx->telegram->deleteMessage($ctx->user->id, $ctx->update->messageId);
        $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('selectPayType', $ctx->baseVars()), [
            'reply_markup' => self::payKeyboard($ctx),
        ]);
    }

    public static function handlePaySelected(Context $ctx, string $wallet): void
    {
        $vazifa = $ctx->settings->get('vazifa', 'Kiritilmagan');
        if ($vazifa === 'Kiritilmagan' || $vazifa === null) {
            $ctx->telegram->answerCallbackQuery($ctx->update->callbackId, [
                'text' => $ctx->templates->render('noChannel', $ctx->baseVars()),
                'show_alert' => true,
            ]);
            return;
        }

        $minAmount = (int) $ctx->settings->get('narx', '3000');
        if ($ctx->user->balance < $minAmount) {
            $ctx->telegram->answerCallbackQuery($ctx->update->callbackId, [
                'text' => $ctx->templates->render('minimum', array_merge($ctx->baseVars(), [
                    'balance' => $ctx->user->balance,
                    'minimum' => $minAmount,
                    'currency' => $ctx->settings->get('valyuta', "so'm"),
                ])),
                'show_alert' => true,
            ]);
            return;
        }

        $ctx->telegram->deleteMessage($ctx->user->id, $ctx->update->messageId);
        $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('sendCard', $ctx->baseVars()), [
            'reply_markup' => Menus::back($ctx->labels),
        ]);
        $ctx->setStateValue('withdraw_wallet', $wallet);
        $ctx->setState('withdraw_wallet');
    }

    public static function handleWalletStep(Context $ctx): void
    {
        $text = (string) $ctx->update->text;

        if ($text === $ctx->labels->back) {
            MainMenu::handleBackToHome($ctx);
            return;
        }

        if (!is_numeric($text)) {
            $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('sendCard', $ctx->baseVars()));
            return;
        }

        $ctx->setStateValue('withdraw_game_id', $text);
        $ctx->setState('withdraw_amount');

        $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('solveMoney', $ctx->baseVars()), [
            'reply_markup' => Keyboards::reply([
                [Keyboards::button((string) $ctx->user->balance)],
                [Keyboards::button($ctx->labels->back)],
            ]),
        ]);
    }

    public static function handleAmountStep(Context $ctx): void
    {
        $text = (string) $ctx->update->text;

        if ($text === $ctx->labels->back) {
            MainMenu::handleBackToHome($ctx);
            return;
        }

        $minAmount = (int) $ctx->settings->get('narx', '3000');
        $currency = $ctx->settings->get('valyuta', "so'm");

        if (!is_numeric($text) || (int) $text < $minAmount) {
            $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('solveMinimum', array_merge($ctx->baseVars(), [
                'balance' => $ctx->user->balance,
                'minimum' => $minAmount,
                'currency' => $currency,
            ])));
            return;
        }

        $amount = (int) $text;
        if ($ctx->user->balance < $amount) {
            $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('lowBalance', $ctx->baseVars()));
            return;
        }

        $ctx->setStateValue('withdraw_amount', $amount);
        $ctx->setState(null);

        $wallet = (string) $ctx->stateValue('withdraw_wallet');
        $gameId = (string) $ctx->stateValue('withdraw_game_id');

        $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('accpeted', array_merge($ctx->baseVars(), [
            'phone' => $gameId,
            'wallet' => $wallet,
            'amount' => $amount,
        ])), [
            'reply_markup' => Keyboards::inline([
                [Keyboards::button($ctx->labels->confirm, ['callback_data' => 'tasdiq'])],
                [Keyboards::button($ctx->labels->cancellation, ['callback_data' => 'bekor'])],
            ]),
        ]);
    }

    public static function handleCancel(Context $ctx): void
    {
        $ctx->clearStateValue('withdraw_wallet');
        $ctx->clearStateValue('withdraw_game_id');
        $ctx->clearStateValue('withdraw_amount');

        $ctx->telegram->editMessageText($ctx->user->id, $ctx->update->messageId, '⏱ <b>Yuklanmoqda...</b>');
        $ctx->telegram->deleteMessage($ctx->user->id, $ctx->update->messageId);

        $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('canceled', $ctx->baseVars()), [
            'reply_markup' => $ctx->mainMenuKeyboard(),
        ]);
    }

    public static function handleConfirm(Context $ctx): void
    {
        $wallet = $ctx->stateValue('withdraw_wallet');
        $gameId = $ctx->stateValue('withdraw_game_id');
        $amount = $ctx->stateValue('withdraw_amount');

        if ($wallet === null || $gameId === null || $amount === null) {
            $ctx->telegram->answerCallbackQuery($ctx->update->callbackId);
            return;
        }
        $amount = (int) $amount;

        $ctx->users->adjustBalance($ctx->user->id, -$amount);
        $ctx->users->adjustWithdrawn($ctx->user->id, $amount);

        $withdrawalId = $ctx->withdrawals->create($ctx->user->id, (string) $wallet, (string) $gameId, $amount);

        $ctx->clearStateValue('withdraw_wallet');
        $ctx->clearStateValue('withdraw_game_id');
        $ctx->clearStateValue('withdraw_amount');

        $ctx->telegram->editMessageText($ctx->user->id, $ctx->update->messageId, '⏱ <b>Yuklanmoqda...</b>');
        $ctx->telegram->deleteMessage($ctx->user->id, $ctx->update->messageId);

        $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('accped', $ctx->baseVars()), [
            'reply_markup' => $ctx->mainMenuKeyboard(),
        ]);

        $safeUsername = htmlspecialchars((string) $ctx->user->username, ENT_QUOTES);
        $ctx->telegram->sendMessage($ctx->config->adminId,
            "💵 <a href='https://t.me/{$safeUsername}'>{$ctx->user->id}</a> <b>almaz yechib olmoqchi!</b>\n\n"
            . "• <b>Yechish turi:</b> {$wallet}\n"
            . "• <b>almaz miqdori:</b> {$amount}\n"
            . "• <b>Free Fire ID raqami:</b> {$gameId}\n\n"
            . "Foydalanuvchini almazini to'lab bermoqchi bo'lsangiz ✅ <b>To'landi</b> tugmasini bosing!",
            [
                'disable_web_page_preview' => true,
                'reply_markup' => Keyboards::inline([
                    [Keyboards::button('🔔 Banlash', ['callback_data' => "wd:ban:{$withdrawalId}"])],
                    [
                        Keyboards::button("✅ To'landi", ['callback_data' => "wd:paid:{$withdrawalId}"]),
                        Keyboards::button("❌ To'lanmadi", ['callback_data' => "wd:unpaid:{$withdrawalId}"]),
                    ],
                ]),
            ]
        );
    }

    public static function handleAdminResolve(Context $ctx, string $action, int $withdrawalId): void
    {
        $withdrawal = $ctx->withdrawals->find($withdrawalId);
        if ($withdrawal === null) {
            $ctx->telegram->answerCallbackQuery($ctx->update->callbackId, [
                'text' => 'So\'rov topilmadi (allaqachon ko\'rib chiqilgan bo\'lishi mumkin).',
                'show_alert' => true,
            ]);
            return;
        }

        $userId = (int) $withdrawal['user_id'];
        $amount = (int) $withdrawal['amount'];
        $wallet = $withdrawal['payment_method'];
        $gameId = $withdrawal['game_id'];
        $user = $ctx->users->find($userId);
        $displayName = $user?->firstName ?? (string) $userId;

        $ctx->telegram->editMessageText($ctx->config->adminId, $ctx->update->messageId, '⏱ <b>Yuklanmoqda...</b>');
        $ctx->telegram->deleteMessage($ctx->config->adminId, $ctx->update->messageId);

        match ($action) {
            'paid' => self::resolvePaid($ctx, $withdrawalId, $userId, $displayName, $gameId, $amount),
            'unpaid' => self::resolveUnpaid($ctx, $withdrawalId, $userId, $displayName, $amount),
            'ban' => self::resolveBan($ctx, $withdrawalId, $userId, $displayName),
            default => null,
        };
    }

    private static function resolvePaid(Context $ctx, int $withdrawalId, int $userId, string $displayName, string $gameId, int $amount): void
    {
        $ctx->withdrawals->markResolved($withdrawalId, 'paid');

        $ctx->telegram->sendMessage($ctx->config->adminId,
            "<a href='tg://user?id={$userId}'>{$displayName}</a> <b>almazlarini yechib olish haqidagi arizasi qabul qilindi.</b>"
        );

        $vazifa = $ctx->settings->get('vazifa', 'Kiritilmagan');
        $advertising = $ctx->templates->render('advertising', ['botname' => $ctx->botUsername]);
        $message = $ctx->telegram->sendMessage($vazifa ?? $ctx->config->adminId, $ctx->templates->render('BeenPaid', [
            'first' => $displayName,
            'id' => $userId,
            'hour' => date('H:i'),
            'date' => date('d.m.Y'),
            'phone' => $gameId,
            'amount' => $amount,
            'advertising' => $advertising,
        ]), [
            'reply_markup' => Keyboards::inline([
                [Keyboards::button($ctx->labels->transition, ['url' => "https://t.me/{$ctx->botUsername}"])],
            ]),
        ]);

        $extra = [];
        if ($message !== null && ($message->ok ?? false) && $vazifa) {
            $kanal = ltrim($vazifa, '@');
            $extra['reply_markup'] = Keyboards::inline([
                [Keyboards::button("TO'LOVLAR KANALI📢", ['url' => "https://t.me/{$kanal}/{$message->result->message_id}"])],
            ]);
        }

        $ctx->telegram->sendMessage($userId, $ctx->templates->render('hasBeenPaid', [
            'first' => $displayName,
            'id' => $userId,
            'hour' => date('H:i'),
            'date' => date('d.m.Y'),
        ]), $extra);
    }

    private static function resolveUnpaid(Context $ctx, int $withdrawalId, int $userId, string $displayName, int $amount): void
    {
        $ctx->users->adjustBalance($userId, $amount);
        $ctx->users->adjustWithdrawn($userId, -$amount);
        $ctx->withdrawals->markResolved($withdrawalId, 'rejected');

        $ctx->telegram->sendMessage($ctx->config->adminId,
            "<a href='tg://user?id={$userId}'>{$displayName}</a> <b>almazlarini yechib olish haqidagi arizasi qabul qilinmadi.</b>"
        );

        $ctx->telegram->sendMessage($userId, $ctx->templates->render('wasNotPaid', [
            'first' => $displayName,
            'id' => $userId,
            'hour' => date('H:i'),
            'date' => date('d.m.Y'),
        ]));
    }

    private static function resolveBan(Context $ctx, int $withdrawalId, int $userId, string $displayName): void
    {
        $ctx->withdrawals->markResolved($withdrawalId, 'banned');
        $ctx->users->setBanned($userId, true);

        $ctx->telegram->sendMessage($ctx->config->adminId,
            "<a href='tg://user?id={$userId}'>{$displayName}</a> <b>almazlarini yechib olish haqidagi arizasi qabul qilinmadi va botdan blocklandi.</b>"
        );

        $ctx->telegram->sendMessage($userId, $ctx->templates->render('block', [
            'first' => $displayName,
            'id' => $userId,
            'hour' => date('H:i'),
            'date' => date('d.m.Y'),
        ]), [
            'reply_markup' => Keyboards::remove(),
        ]);
    }
}
