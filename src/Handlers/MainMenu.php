<?php

declare(strict_types=1);

namespace Bot\Handlers;

use Bot\Context;
use Bot\Support\Keyboards;
use Bot\Support\Menus;

final class MainMenu
{
    public static function handleEarnMenu(Context $ctx): void
    {
        $ctx->telegram->sendMessage($ctx->user->id, 'Siz 💎ALMAZ ISHLASH 💎 bo‘limiga o‘tdingiz', [
            'reply_markup' => Menus::earn($ctx->labels),
        ]);
    }

    public static function handleEarn(Context $ctx): void
    {
        if (!Onboarding::requirePhone($ctx) || !$ctx->gate->check($ctx->user->id, $ctx->labels->check)) {
            return;
        }

        $botUsername = $ctx->botUsername;
        $ctx->telegram->sendPhoto($ctx->user->id, 'https://t.me/TekinAlmazISBOT/2', [
            'caption' => $ctx->templates->render('earnRef', array_merge($ctx->baseVars(), [
                'reflink' => "https://t.me/{$botUsername}?start={$ctx->user->id}",
                'refpay' => $ctx->settings->get('taklif', '250'),
                'refcount' => $ctx->user->referralCount,
                'balance' => $ctx->user->balance,
                'currency' => $ctx->settings->get('valyuta', "so'm"),
            ])),
            'disable_web_page_preview' => true,
            'reply_markup' => Keyboards::inline([
                [Keyboards::button($ctx->labels->share, [
                    'url' => "https://t.me/share/url?url=https://t.me/{$botUsername}?start={$ctx->user->id}",
                ])],
            ]),
        ]);
    }

    public static function handleCabinet(Context $ctx): void
    {
        if (!Onboarding::requirePhone($ctx)) {
            return;
        }

        $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('cabinet', array_merge($ctx->baseVars(), [
            'balance' => $ctx->user->balance,
            'solve' => $ctx->user->withdrawn,
            'refcount' => $ctx->user->referralCount,
            'currency' => $ctx->settings->get('valyuta', "so'm"),
        ])), [
            'reply_markup' => Keyboards::inline([
                [Keyboards::button('🌐 WEB APP', ['web_app' => ['url' => 'https://bot.danatpay.uz/app/']])],
                [Keyboards::button($ctx->labels->solve, ['callback_data' => 'yechish'])],
                [Keyboards::button('🏆 TOP-10 referal', ['callback_data' => 'top'])],
            ]),
        ]);
    }

    public static function handleTopReferrals(Context $ctx): void
    {
        $top = $ctx->users->topReferrers(10);

        if ($top === []) {
            $ctx->telegram->answerCallbackQuery($ctx->update->callbackId, [
                'text' => "Hali hech kim referal qo'shmagan.",
                'show_alert' => true,
            ]);
            return;
        }

        $medals = ['🥇', '🥈', '🥉'];
        $lines = [];
        foreach ($top as $i => $row) {
            $medal = $medals[$i] ?? ($i + 1) . '.';
            $name = $row['first_name'] !== null && $row['first_name'] !== ''
                ? htmlspecialchars($row['first_name'], ENT_QUOTES)
                : 'Foydalanuvchi';
            $lines[] = "{$medal} <a href='tg://user?id={$row['id']}'>{$name}</a> — {$row['referral_count']} ta";
        }

        $ctx->telegram->sendMessage($ctx->user->id,
            "<b>🏆 TOP-10 taklif qiluvchilar:</b>\n\n" . implode("\n", $lines)
        );
    }

    public static function handlePaymentsChannel(Context $ctx): void
    {
        if (!Onboarding::requirePhone($ctx)) {
            return;
        }

        $vazifa = $ctx->settings->get('vazifa', 'Kiritilmagan');
        if ($vazifa === 'Kiritilmagan' || $vazifa === null) {
            $ctx->telegram->sendMessage($ctx->user->id, "<b>To'lovlar kanali ulanmagan!</b>", [
                'disable_web_page_preview' => true,
            ]);
            return;
        }

        $kanal = ltrim($vazifa, '@');
        $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('tolovtext', $ctx->baseVars()), [
            'disable_web_page_preview' => true,
            'reply_markup' => Keyboards::inline([
                [Keyboards::button($ctx->labels->tolov, ['url' => "https://t.me/$kanal"])],
            ]),
        ]);
    }

    public static function handleManual(Context $ctx): void
    {
        if (!Onboarding::requirePhone($ctx)) {
            return;
        }

        $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('manuals', $ctx->baseVars()), [
            'disable_web_page_preview' => true,
            'reply_markup' => Keyboards::inline([
                [Keyboards::button('🛒 @danatshopbot', ['url' => 'https://t.me/danatshopbot'])],
            ]),
        ]);
    }

    public static function handleSupportEntry(Context $ctx): void
    {
        if (!Onboarding::requirePhone($ctx)) {
            return;
        }

        $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('sendSuppMsg', $ctx->baseVars()), [
            'reply_markup' => Menus::back($ctx->labels),
        ]);
        $ctx->setState('yordam');
    }

    public static function handleSupportMessage(Context $ctx): void
    {
        if ($ctx->update->text === $ctx->labels->back) {
            self::handleBackToHome($ctx);
            return;
        }

        $safeText = htmlspecialchars((string) $ctx->update->text, ENT_QUOTES);
        $safeUsername = htmlspecialchars((string) $ctx->user->username, ENT_QUOTES);

        $ctx->telegram->sendMessage($ctx->config->adminId,
            "<a href='https://t.me/{$safeUsername}'>{$ctx->user->id}</a> <b>dan yangi xabar:</b> {$safeText}",
            ['disable_web_page_preview' => true]
        );

        $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('SuppSend', $ctx->baseVars()), [
            'reply_markup' => $ctx->mainMenuKeyboard(),
        ]);
        $ctx->setState(null);
    }

    public static function handleBackToHome(Context $ctx): void
    {
        $ctx->setState(null);
        $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('backHome', $ctx->baseVars()), [
            'reply_markup' => $ctx->mainMenuKeyboard(),
        ]);
    }

    public static function handlePaidSetup(Context $ctx): void
    {
        $ctx->telegram->sendMessage($ctx->user->id,
            "<b>Pullik nastroyka uchun adminga bog'laning:</b>",
            [
                'parse_mode' => 'html',
                'reply_markup' => Keyboards::inline([
                    [Keyboards::button('💬 Adminga yozish', ['url' => 'https://t.me/Es_madara01'])],
                ]),
            ]
        );
    }
}
