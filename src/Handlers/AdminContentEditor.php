<?php

declare(strict_types=1);

namespace Bot\Handlers;

use Bot\Context;
use Bot\Support\Menus;

final class AdminContentEditor
{
    public static function handleButtonMenuEntry(Context $ctx): void
    {
        $ctx->telegram->sendMessage($ctx->config->adminId, '<b>Tugma kodini kiriting:</b>', [
            'reply_markup' => Menus::manage(),
        ]);
        $ctx->setState('button_code');
    }

    public static function handleButtonCodeStep(Context $ctx): void
    {
        $code = (string) $ctx->update->text;
        if ($code === '🗄 Boshqarish') {
            AdminPanel::handleOpen($ctx);
            return;
        }

        if (!$ctx->buttonRepo->exists($code)) {
            $ctx->telegram->sendMessage($ctx->config->adminId,
                "<b>Mavjud bo'lmagan kod tugma kodini kiritdingiz!</b>\n\n"
                . "<i>Qo'llanmada ko'rsatilgan kodlardan foydalaning.</i>");
            return;
        }

        $ctx->setStateValue('editing_button_code', $code);
        $ctx->setState('button_value');
        $ctx->telegram->sendMessage($ctx->config->adminId,
            "<pre>{$code}</pre> <b>qabul qilindi.</b>\n\n<i>Ushbu kod uchun qiymatni kiriting:</i>");
    }

    public static function handleButtonValueStep(Context $ctx): void
    {
        if ($ctx->update->text === '🗄 Boshqarish') {
            AdminPanel::handleOpen($ctx);
            return;
        }

        $code = (string) $ctx->stateValue('editing_button_code');
        $ctx->buttonRepo->set($code, (string) $ctx->update->text);

        $ctx->telegram->sendMessage($ctx->config->adminId, "<b>O'zgartirish yakunlandi!</b>", [
            'reply_markup' => Menus::panel($ctx->labels),
        ]);
        $ctx->clearStateValue('editing_button_code');
        $ctx->setState(null);
    }

    public static function handleTemplateMenuEntry(Context $ctx): void
    {
        $ctx->telegram->sendMessage($ctx->config->adminId, '<b>Matn kodini kiriting:</b>', [
            'reply_markup' => Menus::manage(),
        ]);
        $ctx->setState('template_code');
    }

    public static function handleTemplateCodeStep(Context $ctx): void
    {
        $code = (string) $ctx->update->text;
        if ($code === '🗄 Boshqarish') {
            AdminPanel::handleOpen($ctx);
            return;
        }

        if (!$ctx->templateRepo->exists($code)) {
            $ctx->telegram->sendMessage($ctx->config->adminId,
                "<b>Mavjud bo'lmagan kod matn kodini kiritdingiz!</b>\n\n"
                . "<i>Qo'llanmada ko'rsatilgan kodlardan foydalaning.</i>");
            return;
        }

        $ctx->setStateValue('editing_template_code', $code);
        $ctx->setState('template_value');
        $ctx->telegram->sendMessage($ctx->config->adminId,
            "<pre>{$code}</pre> <b>qabul qilindi.</b>\n\n<i>Ushbu kod uchun qiymatni kiriting:</i>");
    }

    public static function handleTemplateValueStep(Context $ctx): void
    {
        if ($ctx->update->text === '🗄 Boshqarish') {
            AdminPanel::handleOpen($ctx);
            return;
        }

        $code = (string) $ctx->stateValue('editing_template_code');
        $ctx->templateRepo->set($code, (string) $ctx->update->text);

        $ctx->telegram->sendMessage($ctx->config->adminId, "<b>O'zgartirish yakunlandi!</b>", [
            'reply_markup' => Menus::panel($ctx->labels),
        ]);
        $ctx->clearStateValue('editing_template_code');
        $ctx->setState(null);
    }
}
