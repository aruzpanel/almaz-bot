<?php

declare(strict_types=1);

namespace Bot\Handlers;

use Bot\Context;
use Bot\Support\Keyboards;
use Bot\Support\Menus;

final class AdminSettings
{
    public static function menuKeyboard(): string
    {
        return Keyboards::inline([
            [Keyboards::button('📑 Hozirgi holat', ['callback_data' => 'holat'])],
            [
                Keyboards::button('🔗 Taklif narxi', ['callback_data' => 'taklif']),
                Keyboards::button('💶 Valyuta', ['callback_data' => 'valyuta']),
            ],
            [Keyboards::button('💵 Minimal pul yechish narxi', ['callback_data' => 'narx'])],
            [
                Keyboards::button('📎 Admin useri', ['callback_data' => 'admin']),
                Keyboards::button('Yopish', ['callback_data' => 'yopish']),
            ],
        ]);
    }

    public static function handleMenu(Context $ctx): void
    {
        $ctx->telegram->sendMessage($ctx->config->adminId, '<b>Quyidagilardan birini tanlamg:</b>', [
            'reply_markup' => self::menuKeyboard(),
        ]);
    }

    public static function handleMenuCallback(Context $ctx): void
    {
        $ctx->telegram->deleteMessage($ctx->config->adminId, $ctx->update->messageId);
        $ctx->telegram->sendMessage($ctx->config->adminId, '<b>Quyidagilardan birini tanlamg:</b>', [
            'reply_markup' => self::menuKeyboard(),
        ]);
    }

    public static function handleHolat(Context $ctx): void
    {
        $s = $ctx->settings;
        $ctx->telegram->editMessageText($ctx->config->adminId, $ctx->update->messageId,
            "<b>Hozirgi holat:\n\n1. Valyuta:</b> {$s->get('valyuta', "so'm")}\n"
            . "<b>2. Taklif narxi:</b> {$s->get('taklif', '250')} {$s->get('valyuta', "so'm")}\n"
            . "<b>3. Pul yechish narxi:</b> {$s->get('narx', '3000')} {$s->get('valyuta', "so'm")}\n"
            . "<b>4. Admin useri:</b> {$s->get('user', 'Kiritilmagan')}",
            [
                'reply_markup' => Keyboards::inline([
                    [Keyboards::button('◀️ Orqaga', ['callback_data' => 'asosiy'])],
                ]),
            ]
        );
    }

    private static function askFor(Context $ctx, string $prompt, string $state): void
    {
        $ctx->telegram->deleteMessage($ctx->config->adminId, $ctx->update->messageId);
        $ctx->telegram->sendMessage($ctx->config->adminId, "<b>{$prompt}</b>", [
            'reply_markup' => Menus::manage(),
        ]);
        $ctx->setState($state);
    }

    public static function handleTaklifEntry(Context $ctx): void
    {
        self::askFor($ctx, 'Taklif narxini yuboring:', 'settings_taklif');
    }

    public static function handleValyutaEntry(Context $ctx): void
    {
        self::askFor($ctx, 'Pul birligini yuboring:', 'settings_valyuta');
    }

    public static function handleNarxEntry(Context $ctx): void
    {
        self::askFor($ctx, "Minimal pul yechish narxini yuboring:", 'settings_narx');
    }

    public static function handleAdminUserEntry(Context $ctx): void
    {
        self::askFor($ctx, 'Admin userini yuboring:', 'settings_admin_user');
    }

    private static function saveAndConfirm(Context $ctx, string $key, string $value): void
    {
        $ctx->settings->set($key, $value);
        $ctx->setState(null);
        $ctx->telegram->sendMessage($ctx->config->adminId, "<b>Muvaffaqiyatli o'zgartirildi!</b>", [
            'reply_markup' => Menus::panel($ctx->labels),
        ]);
    }

    public static function handleTaklifStep(Context $ctx): void
    {
        if ($ctx->update->text === '🗄 Boshqarish') {
            AdminPanel::handleOpen($ctx);
            return;
        }
        self::saveAndConfirm($ctx, 'taklif', (string) $ctx->update->text);
    }

    public static function handleValyutaStep(Context $ctx): void
    {
        if ($ctx->update->text === '🗄 Boshqarish') {
            AdminPanel::handleOpen($ctx);
            return;
        }
        self::saveAndConfirm($ctx, 'valyuta', (string) $ctx->update->text);
    }

    public static function handleNarxStep(Context $ctx): void
    {
        if ($ctx->update->text === '🗄 Boshqarish') {
            AdminPanel::handleOpen($ctx);
            return;
        }
        self::saveAndConfirm($ctx, 'narx', (string) $ctx->update->text);
    }

    public static function handleAdminUserStep(Context $ctx): void
    {
        if ($ctx->update->text === '🗄 Boshqarish') {
            AdminPanel::handleOpen($ctx);
            return;
        }
        self::saveAndConfirm($ctx, 'user', (string) $ctx->update->text);
    }
}
