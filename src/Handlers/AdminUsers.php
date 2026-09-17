<?php

declare(strict_types=1);

namespace Bot\Handlers;

use Bot\Context;
use Bot\Support\Keyboards;
use Bot\Support\Menus;

final class AdminUsers
{
    public static function handleMenuEntry(Context $ctx): void
    {
        $ctx->telegram->sendMessage($ctx->config->adminId, "<b>Kerakli foydalanuvchining ID raqamini kiriting:</b>", [
            'reply_markup' => Menus::manage(),
        ]);
        $ctx->setState('user_lookup');
    }

    private static function renderCard(Context $ctx, int $targetId): void
    {
        $target = $ctx->users->find($targetId);
        if ($target === null) {
            return;
        }

        $banLabel = $target->isBanned ? '🔕 Bandan olish' : '🔔 Banlash';
        $currency = $ctx->settings->get('valyuta', "so'm");

        $ctx->telegram->sendMessage($ctx->config->adminId,
            "<b>Foydalanuvchi topildi!\n\nID:</b> <a href='tg://user?id={$targetId}'>{$targetId}</a>\n"
            . "<b>Balans: {$target->balance} {$currency}\nTakliflar: {$target->referralCount} ta</b>",
            [
                'reply_markup' => Keyboards::inline([
                    [Keyboards::button($banLabel, ['callback_data' => 'ban'])],
                    [
                        Keyboards::button("➕ almaz qo'shish", ['callback_data' => 'plus']),
                        Keyboards::button('➖ almaz ayirish', ['callback_data' => 'minus']),
                    ],
                ]),
            ]
        );
    }

    public static function handleLookupStep(Context $ctx): void
    {
        $text = (string) $ctx->update->text;
        if ($text === '🗄 Boshqarish') {
            AdminPanel::handleOpen($ctx);
            return;
        }

        if (!is_numeric($text) || !$ctx->users->exists((int) $text)) {
            $ctx->telegram->sendMessage($ctx->config->adminId,
                "<b>Foydalanuvchi topilmadi.</b>\n\nQayta urinib ko'ring:");
            return;
        }

        $targetId = (int) $text;
        $ctx->setStateValue('selected_user_id', $targetId);
        $ctx->setState(null);

        self::renderCard($ctx, $targetId);
    }

    public static function handleFoydalanuvchiCallback(Context $ctx): void
    {
        $targetId = (int) $ctx->stateValue('selected_user_id', 0);
        if ($targetId === 0) {
            return;
        }

        $ctx->telegram->deleteMessage($ctx->config->adminId, $ctx->update->messageId);
        self::renderCard($ctx, $targetId);
    }

    public static function handlePlusEntry(Context $ctx): void
    {
        $targetId = (int) $ctx->stateValue('selected_user_id', 0);
        if ($targetId === 0) {
            return;
        }

        $ctx->telegram->editMessageText($ctx->config->adminId, $ctx->update->messageId,
            "<a href='tg://user?id={$targetId}'>{$targetId}</a> <b>ning hisobiga qancha pul qo'shmoqchisiz?</b>",
            [
                'reply_markup' => Keyboards::inline([
                    [Keyboards::button('◀️ Orqaga', ['callback_data' => 'foydalanuvchi'])],
                ]),
            ]
        );
        $ctx->setState('balance_plus');
    }

    public static function handleMinusEntry(Context $ctx): void
    {
        $targetId = (int) $ctx->stateValue('selected_user_id', 0);
        if ($targetId === 0) {
            return;
        }

        $ctx->telegram->editMessageText($ctx->config->adminId, $ctx->update->messageId,
            "<a href='tg://user?id={$targetId}'>{$targetId}</a> <b>ning hisobiga qancha pul ayirmoqchisiz?</b>",
            [
                'reply_markup' => Keyboards::inline([
                    [Keyboards::button('◀️ Orqaga', ['callback_data' => 'foydalanuvchi'])],
                ]),
            ]
        );
        $ctx->setState('balance_minus');
    }

    private static function adjustStep(Context $ctx, int $sign): void
    {
        $text = (string) $ctx->update->text;
        $targetId = (int) $ctx->stateValue('selected_user_id', 0);

        if (!is_numeric($text) || $targetId === 0) {
            $ctx->telegram->sendMessage($ctx->config->adminId, '<b>Faqat raqamlardan foydalaning!</b>');
            return;
        }

        $amount = (int) $text;
        $currency = $ctx->settings->get('valyuta', "so'm");
        $ctx->users->adjustBalance($targetId, $sign * $amount);

        $verb = $sign > 0 ? "to'ldirildi" : "olib tashlandi";
        $userSuffix = $sign > 0 ? '' : 'dan';
        $adminSuffix = $sign > 0 ? 'ga' : 'dan';
        $adminVerb = $sign > 0 ? "qo'shildi!" : 'olib tashlandi!';

        $ctx->telegram->sendMessage($targetId,
            "<b>Adminlar tomonidan hisobingiz{$userSuffix} {$amount} {$currency} {$verb}</b>",
            ['reply_markup' => $ctx->mainMenuKeyboard()]
        );
        $ctx->telegram->sendMessage($ctx->config->adminId,
            "<b>Foydalanuvchi hisobi{$adminSuffix} {$amount} {$currency} {$adminVerb}</b>",
            ['reply_markup' => Menus::panel($ctx->labels)]
        );

        $ctx->clearStateValue('selected_user_id');
        $ctx->setState(null);
    }

    public static function handlePlusStep(Context $ctx): void
    {
        self::adjustStep($ctx, 1);
    }

    public static function handleMinusStep(Context $ctx): void
    {
        self::adjustStep($ctx, -1);
    }

    public static function handleBanToggle(Context $ctx): void
    {
        $targetId = (int) $ctx->stateValue('selected_user_id', 0);
        if ($targetId === 0) {
            return;
        }

        if ($targetId === $ctx->config->adminId) {
            $ctx->telegram->answerCallbackQuery($ctx->update->callbackId, [
                'text' => 'Asosiy adminlarni blocklash mumkin emas!',
                'show_alert' => true,
            ]);
            return;
        }

        $target = $ctx->users->find($targetId);
        $nowBanned = !($target?->isBanned ?? false);
        $ctx->users->setBanned($targetId, $nowBanned);

        $text = $nowBanned
            ? "<b>Foydalanuvchi ({$targetId}) banlandi!</b>"
            : "<b>Foydalanuvchi ({$targetId}) bandan olindi!</b>";

        $ctx->telegram->editMessageText($ctx->config->adminId, $ctx->update->messageId, $text, [
            'reply_markup' => Keyboards::inline([
                [Keyboards::button('◀️ Orqaga', ['callback_data' => 'foydalanuvchi'])],
            ]),
        ]);
    }
}
