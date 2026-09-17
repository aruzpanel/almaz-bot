<?php

declare(strict_types=1);

namespace Bot\Handlers;

use Bot\Context;
use Bot\Support\Keyboards;

final class Onboarding
{
    /** Mirrors the legacy number(): prompts for a phone number if the user has none yet. */
    public static function requirePhone(Context $ctx): bool
    {
        if ($ctx->user->hasPhone()) {
            return true;
        }

        $ctx->setState('request_contact');
        $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('textPhone', $ctx->baseVars()), [
            'reply_markup' => Keyboards::reply([
                [Keyboards::requestContactButton($ctx->labels->getPhone)],
            ]),
        ]);

        return false;
    }

    private const WELCOME_BANNER = 'https://t.me/TekinAlmazISBOT/2';

    public static function sendWelcome(Context $ctx): void
    {
        $ctx->telegram->sendPhoto($ctx->user->id, self::WELCOME_BANNER, [
            'caption' => $ctx->templates->render('welcome', $ctx->baseVars()),
            'reply_markup' => $ctx->mainMenuKeyboard(),
        ]);
    }

    public static function handleStartCommand(Context $ctx): void
    {
        if (!self::requirePhone($ctx)) {
            return;
        }

        self::sendWelcome($ctx);
    }

    public static function handleReferralStart(Context $ctx, int $referrerId, bool $userExistedBefore): void
    {
        if ($referrerId === $ctx->user->id) {
            self::sendWelcome($ctx);
            return;
        }

        if ($userExistedBefore) {
            self::sendWelcome($ctx);
            return;
        }

        $ctx->telegram->sendMessage($referrerId, $ctx->templates->render('newRef', [
            'refid' => $ctx->user->id,
        ]));

        $ctx->referrals->registerPending($ctx->user->id, $referrerId);

        if ($ctx->gate->check($ctx->user->id, $ctx->labels->check)) {
            $ctx->referrals->creditIfPending($ctx->user->id);
            self::sendWelcome($ctx);
        }
    }

    public static function handleCheckCallback(Context $ctx): void
    {
        $ctx->telegram->deleteMessage($ctx->user->id, $ctx->update->messageId);

        if (!$ctx->gate->check($ctx->user->id, $ctx->labels->check)) {
            return;
        }

        $ctx->referrals->creditIfPending($ctx->user->id);
        self::sendWelcome($ctx);
    }

    public static function handleContact(Context $ctx): void
    {
        $phone = str_replace('+', '', (string) $ctx->update->contactPhone);

        $valid = false;
        foreach ($ctx->config->allowedPhonePrefixes as $prefix) {
            if (str_starts_with($phone, $prefix) && strlen($phone) >= 11 && strlen($phone) <= 13) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('noPhone', $ctx->baseVars()), [
                'reply_to_message_id' => $ctx->update->messageId,
                'reply_markup' => Keyboards::remove(),
            ]);
            $ctx->users->setBanned($ctx->user->id, true);
            $ctx->setState(null);
            return;
        }

        if ($ctx->update->contactUserId !== $ctx->user->id) {
            $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('textPhone', $ctx->baseVars()), [
                'reply_markup' => Keyboards::reply([
                    [Keyboards::requestContactButton($ctx->labels->getPhone)],
                ]),
            ]);
            return;
        }

        $ctx->user->phone = $phone;
        $ctx->users->save($ctx->user);
        $ctx->setState(null);

        $ctx->telegram->sendMessage($ctx->user->id, $ctx->templates->render('conPhone', array_merge($ctx->baseVars(), [
            'phone' => $phone,
        ])), [
            'reply_markup' => Keyboards::inline([
                [Keyboards::button($ctx->labels->contiune, ['callback_data' => 'davom'])],
            ]),
        ]);
    }

    public static function handleContinueCallback(Context $ctx): void
    {
        $ctx->telegram->deleteMessage($ctx->user->id, $ctx->update->messageId);
        self::sendWelcome($ctx);
    }
}
