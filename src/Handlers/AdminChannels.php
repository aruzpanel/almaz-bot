<?php

declare(strict_types=1);

namespace Bot\Handlers;

use Bot\Context;
use Bot\Support\Keyboards;
use Bot\Support\Menus;

final class AdminChannels
{
    private static function rootKeyboard(): string
    {
        return Keyboards::inline([
            [Keyboards::button('🔐 Majburiy obunalar', ['callback_data' => 'majburiy'])],
            [Keyboards::button("*⃣ Qo'shimcha kanallar", ['callback_data' => 'qoshimcha'])],
            [Keyboards::button('Yopish', ['callback_data' => 'yopish'])],
        ]);
    }

    public static function handleMenu(Context $ctx): void
    {
        $ctx->telegram->sendMessage($ctx->config->adminId, '<b>Quyidagilardan birini tanlang:</b>', [
            'reply_markup' => self::rootKeyboard(),
        ]);
    }

    public static function handleMenuCallback(Context $ctx): void
    {
        $ctx->telegram->deleteMessage($ctx->config->adminId, $ctx->update->messageId);
        $ctx->telegram->sendMessage($ctx->config->adminId, '<b>Quyidagilardan birini tanlang:</b>', [
            'reply_markup' => self::rootKeyboard(),
        ]);
    }

    public static function handleMajburiy(Context $ctx): void
    {
        $ctx->telegram->editMessageText($ctx->config->adminId, $ctx->update->messageId, '<b>Kuting...</b>');
        $ctx->telegram->editMessageText($ctx->config->adminId, $ctx->update->messageId,
            "<b>Majburiy obunalarni sozlash bo'limidasiz:</b>",
            [
                'reply_markup' => Keyboards::inline([
                    [Keyboards::button("➕ Qo'shish", ['callback_data' => 'qoshish'])],
                    [
                        Keyboards::button("📑 Ro'yxat", ['callback_data' => 'royxat']),
                        Keyboards::button("🗑 O'chirish", ['callback_data' => 'ochirish']),
                    ],
                    [Keyboards::button('◀️ Orqaga', ['callback_data' => 'kanallar'])],
                ]),
            ]
        );
    }

    public static function handleQoshishEntry(Context $ctx): void
    {
        $ctx->telegram->deleteMessage($ctx->config->adminId, $ctx->update->messageId);
        $ctx->telegram->sendMessage($ctx->config->adminId, "<b>Kanalingiz userini kiriting:\n\nNamuna:</b> @ORGBuilder", [
            'reply_markup' => Menus::manage(),
        ]);
        $ctx->setState('channel_add');
    }

    public static function handleQoshishStep(Context $ctx): void
    {
        $text = (string) $ctx->update->text;
        if ($text === '🗄 Boshqarish') {
            AdminPanel::handleOpen($ctx);
            return;
        }

        if (!str_contains($text, '@')) {
            $ctx->telegram->sendMessage($ctx->config->adminId, "<b>Kanalingiz useri yuboring:\n\nNamuna:</b> @ORGBuilder");
            return;
        }

        $username = ltrim(trim($text), '@');
        $chat = $ctx->telegram->getChat('@' . $username);
        $title = ($chat && ($chat->ok ?? false)) ? $chat->result->title : null;

        $ctx->channels->add($username, $title);
        $ctx->setState(null);
        $ctx->telegram->sendMessage($ctx->config->adminId, "<b>{$text} - kanal qo'shildi!</b>", [
            'reply_markup' => Menus::panel($ctx->labels),
        ]);
    }

    public static function handleOchirish(Context $ctx): void
    {
        $ctx->telegram->deleteMessage($ctx->config->adminId, $ctx->update->messageId);
        $ctx->telegram->sendMessage($ctx->config->adminId, "<b>Kanallar o'chirildi</b>");
        $ctx->channels->clear();
    }

    public static function handleRoyxat(Context $ctx): void
    {
        $channels = $ctx->channels->all();

        $ctx->telegram->editMessageText($ctx->config->adminId, $ctx->update->messageId, '<b>Kuting...</b>');

        if ($channels === []) {
            $ctx->telegram->editMessageText($ctx->config->adminId, $ctx->update->messageId,
                "📂 <b>Kanallar ro'yxati bo'sh!</b>",
                [
                    'reply_markup' => Keyboards::inline([
                        [Keyboards::button('◀️ Orqaga', ['callback_data' => 'majburiy'])],
                    ]),
                ]
            );
            return;
        }

        $list = implode("\n", array_map(static fn (string $u) => '@' . $u, $channels));
        $ctx->telegram->editMessageText($ctx->config->adminId, $ctx->update->messageId,
            "<b>📢 Kanallar ro'yxati:</b>\n\n{$list}\n\n<b>Ulangan kanallar soni:</b> " . count($channels) . ' ta',
            [
                'reply_markup' => Keyboards::inline([
                    [Keyboards::button('◀️ Orqaga', ['callback_data' => 'majburiy'])],
                ]),
            ]
        );
    }

    public static function handleQoshimcha(Context $ctx): void
    {
        $vazifa = $ctx->settings->get('vazifa', 'Kiritilmagan');
        $ctx->telegram->editMessageText($ctx->config->adminId, $ctx->update->messageId, '<b>Kuting...</b>');
        $ctx->telegram->editMessageText($ctx->config->adminId, $ctx->update->messageId,
            "<b>Quyidagilardan birini tanlang:\n\nHozirgi holat:\nTo'lovlar uchun kanal:</b> {$vazifa}",
            [
                'reply_markup' => Keyboards::inline([
                    [Keyboards::button("🆕️ To'lovlar uchun", ['callback_data' => 'vazifa'])],
                    [Keyboards::button('◀️ Orqaga', ['callback_data' => 'kanallar'])],
                ]),
            ]
        );
    }

    public static function handleVazifaEntry(Context $ctx): void
    {
        $ctx->telegram->deleteMessage($ctx->config->adminId, $ctx->update->messageId);
        $ctx->telegram->sendMessage($ctx->config->adminId, '<b>Kanalingiz userini kiriting:</b>', [
            'reply_markup' => Menus::manage(),
        ]);
        $ctx->setState('payments_channel');
    }

    public static function handleVazifaStep(Context $ctx): void
    {
        $text = (string) $ctx->update->text;
        if ($text === '🗄 Boshqarish') {
            AdminPanel::handleOpen($ctx);
            return;
        }

        if (!str_contains($text, '@')) {
            $ctx->telegram->sendMessage($ctx->config->adminId,
                "⚠️ <b>Kanal manzili kiritishda xatolik!</b>\n\nQayta urinib ko'ring:");
            return;
        }

        $chat = $ctx->telegram->getChat($text);
        $chatUsername = $chat->result->username ?? null;

        if ($chatUsername === null || !$ctx->telegram->isChannelAdmin($chatUsername)) {
            $ctx->telegram->sendMessage($ctx->config->adminId,
                "<b>Bot ushbu kanalda admin emas yoki noto'g'ri kanal manzili yuborildi!</b>");
            return;
        }

        $ctx->settings->set('vazifa', '@' . $chatUsername);
        $ctx->setState(null);
        $ctx->telegram->sendMessage($ctx->config->adminId, "<b>Muvaffaqiyatli o'zgartirildi!</b>", [
            'reply_markup' => Menus::panel($ctx->labels),
        ]);
    }
}
