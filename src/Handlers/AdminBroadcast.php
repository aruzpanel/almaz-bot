<?php

declare(strict_types=1);

namespace Bot\Handlers;

use Bot\Context;
use Bot\Support\Keyboards;
use Bot\Support\Menus;

final class AdminBroadcast
{
    public static function handleMenu(Context $ctx): void
    {
        $ctx->telegram->sendMessage($ctx->config->adminId, "<b>Yuboriladigan xabar turini tanlang;</b>", [
            'reply_markup' => Keyboards::inline([
                [
                    Keyboards::button('Oddiy xabar', ['callback_data' => 'send']),
                    Keyboards::button('Forward xabar', ['callback_data' => 'forsend']),
                ],
                [Keyboards::button('Foydalanuvchiga xabar', ['callback_data' => 'user'])],
            ]),
        ]);
    }

    public static function handleUserEntry(Context $ctx): void
    {
        $ctx->telegram->deleteMessage($ctx->config->adminId, $ctx->update->messageId);
        $ctx->telegram->sendMessage($ctx->config->adminId, '<b>Foydalanuvchi iD raqamini kiriting:</b>', [
            'reply_markup' => Menus::manage(),
        ]);
        $ctx->setState('broadcast_target');
    }

    public static function handleUserIdStep(Context $ctx): void
    {
        $text = (string) $ctx->update->text;
        if ($text === '🗄 Boshqarish') {
            AdminPanel::handleOpen($ctx);
            return;
        }

        if (!is_numeric($text)) {
            $ctx->telegram->sendMessage($ctx->config->adminId, '<b>Faqat raqamlardan foydalaning!</b>');
            return;
        }

        $ctx->setStateValue('broadcast_target', (int) $text);
        $ctx->setState('broadcast_message');
        $ctx->telegram->sendMessage($ctx->config->adminId, '<b>Xabaringizni kiriting:</b>');
    }

    public static function handleMessageStep(Context $ctx): void
    {
        $text = (string) $ctx->update->text;
        if ($text === '🗄 Boshqarish') {
            AdminPanel::handleOpen($ctx);
            return;
        }

        $targetId = (int) $ctx->stateValue('broadcast_target', 0);
        if ($targetId !== 0) {
            $ctx->telegram->sendMessage($targetId, $text, ['disable_web_page_preview' => true]);
        }

        $ctx->telegram->sendMessage($ctx->config->adminId, '<b>Xabaringiz yuborildi ✅</b>', [
            'reply_markup' => Menus::panel($ctx->labels),
        ]);
        $ctx->clearStateValue('broadcast_target');
        $ctx->setState(null);
    }

    public static function handleSendEntry(Context $ctx): void
    {
        $ctx->telegram->deleteMessage($ctx->config->adminId, $ctx->update->messageId);
        $ctx->telegram->sendMessage($ctx->config->adminId, '*Xabaringizni kiriting:*', [
            'parse_mode' => 'markdown',
            'reply_markup' => Menus::manage(),
        ]);
        $ctx->setState('broadcast_all');
    }

    public static function handleSendStep(Context $ctx): void
    {
        $text = (string) $ctx->update->text;
        if ($text === '🗄 Boshqarish') {
            AdminPanel::handleOpen($ctx);
            return;
        }

        // Sent by BroadcastSender after the webhook has answered, not here:
        // looping over every user inside the request made Telegram time out and retry.
        $ctx->broadcasts->enqueueText($text);

        $ctx->telegram->sendMessage($ctx->config->adminId, sprintf(
            "<b>⏳ Xabar navbatga qo'yildi.</b>
%d ta foydalanuvchiga yuborilmoqda — tugagach xabar beraman.",
            $ctx->users->count()
        ), [
            'reply_markup' => Menus::panel($ctx->labels),
        ]);
        $ctx->setState(null);
    }

    public static function handleForsendEntry(Context $ctx): void
    {
        $ctx->telegram->deleteMessage($ctx->config->adminId, $ctx->update->messageId);
        $ctx->telegram->sendMessage($ctx->config->adminId, '*Xabaringizni yuboring:*', [
            'parse_mode' => 'markdown',
            'reply_markup' => Menus::manage(),
        ]);
        $ctx->setState('broadcast_all_forward');
    }

    public static function handleForsendStep(Context $ctx): void
    {
        if ($ctx->update->text === '🗄 Boshqarish') {
            AdminPanel::handleOpen($ctx);
            return;
        }

        $ctx->broadcasts->enqueueForward($ctx->config->adminId, (int) $ctx->update->messageId);

        $ctx->telegram->sendMessage($ctx->config->adminId, sprintf(
            "<b>⏳ Xabar navbatga qo'yildi.</b>
%d ta foydalanuvchiga yuborilmoqda — tugagach xabar beraman.",
            $ctx->users->count()
        ), [
            'reply_markup' => Menus::panel($ctx->labels),
        ]);
        $ctx->setState(null);
    }
}
