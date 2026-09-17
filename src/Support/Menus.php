<?php

declare(strict_types=1);

namespace Bot\Support;

final class Menus
{
    public static function main(Labels $l, bool $isAdmin): string
    {
        $rows = [
            [Keyboards::button('💎ALMAZ ISHLASH 💎')],
            [Keyboards::button($l->cabinet), Keyboards::button($l->tolov)],
            [Keyboards::button($l->support), Keyboards::button($l->manual)],
        ];

        $rows[] = $isAdmin
            ? [Keyboards::button('⚙️PULLI NASTROYKA ⚙️'), Keyboards::button('🗄 Boshqarish')]
            : [Keyboards::button('⚙️PULLI NASTROYKA ⚙️')];

        return Keyboards::reply($rows);
    }

    public static function earn(Labels $l): string
    {
        return Keyboards::reply([
            [Keyboards::button($l->earn)],
            [Keyboards::button($l->back)],
        ]);
    }

    public static function back(Labels $l): string
    {
        return Keyboards::reply([[Keyboards::button($l->back)]]);
    }

    public static function manage(): string
    {
        return Keyboards::reply([[Keyboards::button('🗄 Boshqarish')]]);
    }

    public static function panel(Labels $l): string
    {
        return Keyboards::reply([
            [Keyboards::button('⚙ Asosiy sozlamalar')],
            [Keyboards::button('📢 Kanallar'), Keyboards::button('📊 Statistika')],
            [Keyboards::button('🔎 Foydalanuvchini boshqarish')],
            [Keyboards::button('🎛 Tugmalar'), Keyboards::button('📃 Matnlar')],
            [Keyboards::button("💳 To'lov tizimi")],
            [Keyboards::button('📨 Xabarnoma'), Keyboards::button($l->back)],
        ]);
    }
}
