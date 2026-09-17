<?php

declare(strict_types=1);

namespace Bot\Handlers;

use Bot\Context;
use Bot\Support\Keyboards;
use Bot\Support\Menus;

final class AdminPanel
{
    public static function handleOpen(Context $ctx): void
    {
        $ctx->setState(null);
        $ctx->telegram->sendMessage($ctx->config->adminId, '<b>Boshqaruv panelidasiz.</b>', [
            'reply_markup' => Menus::panel($ctx->labels),
        ]);
    }

    public static function handleStatistika(Context $ctx): void
    {
        $total = $ctx->users->count();
        $today = $ctx->users->countCreatedSince(gmdate('Y-m-d 00:00:00'));
        $week = $ctx->users->countCreatedSince(gmdate('Y-m-d 00:00:00', strtotime('-6 days')));
        $banned = $ctx->users->countBanned();
        $balance = $ctx->users->sumBalance();
        $withdrawn = $ctx->users->sumWithdrawn();
        $pendingWithdrawals = $ctx->withdrawals->countByStatus('pending');
        $paidWithdrawals = $ctx->withdrawals->countByStatus('paid');
        $currency = $ctx->settings->get('valyuta', "so'm");
        $load = sys_getloadavg();

        $ctx->telegram->sendMessage($ctx->config->adminId,
            "<b>💡 O'rtacha yuklanish:</b> <code>{$load[0]}</code>\n\n"
            . "<b>👥 Foydalanuvchilar:</b> {$total} ta\n"
            . "<b>🆕 Bugun qo'shilgan:</b> {$today} ta\n"
            . "<b>📅 Shu hafta qo'shilgan:</b> {$week} ta\n"
            . "<b>🚫 Banlanganlar:</b> {$banned} ta\n\n"
            . "<b>💎 Umumiy balans (hali yechilmagan):</b> {$balance} {$currency}\n"
            . "<b>💳 Jami yechib olingan:</b> {$withdrawn} {$currency}\n"
            . "<b>⏳ Ko'rib chiqilmagan so'rovlar:</b> {$pendingWithdrawals} ta\n"
            . "<b>✅ To'langan so'rovlar:</b> {$paidWithdrawals} ta",
            [
                'reply_markup' => Keyboards::inline([
                    [Keyboards::button('Yopish', ['callback_data' => 'yopish'])],
                ]),
            ]
        );
    }

    public static function handleClose(Context $ctx): void
    {
        $ctx->telegram->deleteMessage($ctx->config->adminId, $ctx->update->messageId);
    }
}
