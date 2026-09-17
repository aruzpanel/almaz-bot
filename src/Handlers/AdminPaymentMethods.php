<?php

declare(strict_types=1);

namespace Bot\Handlers;

use Bot\Context;
use Bot\Support\Keyboards;
use Bot\Support\Menus;

final class AdminPaymentMethods
{
    private static function listKeyboard(Context $ctx): string
    {
        $rows = [];
        foreach ($ctx->paymentMethods->all() as $name) {
            $rows[] = [Keyboards::button("{$name} - ni o'chirish", ['callback_data' => 'del-' . $name])];
        }
        $rows[] = [Keyboards::button("➕ To'lov tizimi qo'shish", ['callback_data' => 'new'])];

        return Keyboards::inline($rows);
    }

    public static function handleMenu(Context $ctx): void
    {
        if ($ctx->paymentMethods->all() === []) {
            $ctx->telegram->sendMessage($ctx->config->adminId, '<b>Quyidagilardan birini tanlang:</b>', [
                'reply_markup' => Keyboards::inline([
                    [Keyboards::button("➕ To'lov tizimi qo'shish", ['callback_data' => 'new'])],
                ]),
            ]);
            return;
        }

        $ctx->telegram->sendMessage($ctx->config->adminId, '<b>Quyidagilardan birini tanlang:</b>', [
            'reply_markup' => self::listKeyboard($ctx),
        ]);
    }

    public static function handleMenuCallback(Context $ctx): void
    {
        $ctx->telegram->deleteMessage($ctx->config->adminId, $ctx->update->messageId);
        self::handleMenu($ctx);
    }

    public static function handleDelete(Context $ctx, string $name): void
    {
        $ctx->paymentMethods->remove($name);

        $ctx->telegram->editMessageText($ctx->config->adminId, $ctx->update->messageId, '<b>Kuting...</b>');
        $ctx->telegram->editMessageText($ctx->config->adminId, $ctx->update->messageId,
            "{$name} - <b>To'lov tizimi olib tashlandi.</b>",
            [
                'reply_markup' => Keyboards::inline([
                    [Keyboards::button('◀️ Orqaga', ['callback_data' => 'hamyon'])],
                ]),
            ]
        );
    }

    public static function handleNewEntry(Context $ctx): void
    {
        $ctx->telegram->deleteMessage($ctx->config->adminId, $ctx->update->messageId);
        $ctx->telegram->sendMessage($ctx->config->adminId, "<b>Yangi to'lov tizimi nomini yuboring:</b>", [
            'reply_markup' => Menus::manage(),
        ]);
        $ctx->setState('payment_method_add');
    }

    public static function handleNewStep(Context $ctx): void
    {
        $text = (string) $ctx->update->text;
        if ($text === '🗄 Boshqarish') {
            AdminPanel::handleOpen($ctx);
            return;
        }

        $ctx->paymentMethods->add($text);
        $ctx->setState(null);
        $ctx->telegram->sendMessage($ctx->config->adminId, "<b>Yangi to'lov tizimi qo'shildi!</b>", [
            'reply_markup' => Menus::panel($ctx->labels),
        ]);
    }
}
