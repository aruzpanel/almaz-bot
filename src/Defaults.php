<?php

declare(strict_types=1);

namespace Bot;

final class Defaults
{
    public static function settings(): array
    {
        return [
            'taklif' => '250',
            'valyuta' => "so'm",
            'narx' => '3000',
            'vazifa' => 'Kiritilmagan',
            'user' => 'Kiritilmagan',
        ];
    }

    public static function buttons(): array
    {
        return [
            'earn' => '📎 Taklif havolasi',
            'solve' => '📥 Almaz yechish',
            'cabinet' => '📨ISHLAGAN ALMAZIM 📨',
            'tolov' => "TO'LOVLAR KANALI📢",
            'support' => 'ADMINGA SAVOL ✏️',
            'manual' => '✅ALMAZ SOTIB OLISH ✅',
            'back' => '◀️ Orqaga',
            'getPhone' => '📱 Telefon raqamni yuborish',
            'check' => '🔄 Tekshirish',
            'contiune' => '✅ Davom etish',
            'share' => '↗️ Ulashish',
            'cancellation' => '🚫 Bekor qilish',
            'confirm' => '✅ Tasdiqlash',
            'transition' => "🤖 Botga o'tish",
        ];
    }

    public static function templates(): array
    {
        return [
            'welcome' => '🖥 <b>Asosiy menyudasiz.</b>',
            'subChannels' => "⚠️ <b>Botdan foydalanish uchun, quyidagi kanallarga obuna bo'ling:</b>",
            'tolovtext' => "<b>⤵️ Quyidagi kanal orqali to'livlarni kuzatib boring:</b>",
            'newRef' => "📳 <b>Sizda yangi</b> <a href='tg://user?id=%refid%'>taklif</a> <b>mavjud!</b>",
            'checkRef' => "✅ <b>Hisobingizga %refpay% %currency% qo'shildi!</b>",
            'backHome' => '🖥 <b>Asosiy menyuga qaytdingiz.</b>',
            'textPhone' => "📲 <b>Botdan ro'yxatdan o'tish uchun quyidagi tugma orqali telefon raqamingizni yuboring:</b>",
            'conPhone' => "<b>✅ Telefon raqamingiz qabul qilindi:</b> %phone%\n\n<i>Botdan foydalanish boshlash uchun quyidagi tugmani bosing:</i>",
            'noPhone' => "<b>Kechirasiz, Botdan faqat O'zbekiston fuqarolari foydalanishi mumkin.</b>",
            'earnRef' => "<blockquote>✅ Eyyy! Sizga tekin almaz kerakmi?!\n\n➡️ Shu kungacha almaz olmagan boʻlsangiz, yaxshi qilibsiz. Endi bepulga olishingiz mumkin.\n\n➡️ Shunchaki botga start bosing va berilgan havola orqali doʻstlaringizni taklif qiling. Evaziga bot sizga almazlar beradi!\n\n🔐 Pastdagi havola orqali doʻstlaringizga ulashing::</blockquote>\n\n%reflink%\n\n<i>Yuqoridagi taklif havolangizni do'stlaringizga tarqating va har bir to'liq ro'yxatdan o'tgan taklifingiz uchun %refpay% %currency% almazlaringizga qo'shiladi.</i>",
            'cabinet' => " <blockquote>🔑Sizning ID raqamingiz: <code>%id%</code></blockquote>\n\n<blockquote>💵Asosiy balansingiz: %balance% %currency%</blockquote>\n <blockquote>👤Takliflaringiz soni: %refcount%</blockquote>\n\n<blockquote>💳 Yechib olgan Almazlaringiz: %solve% %currency%</blockquote>",
            'selectPayType' => "👇 <b>Quyidagi to'lov tizimlaridan birini tanlang:</b>",
            'minimum' => "⛔ Jarayonni davom ettira olmaysiz!\n\nMinimal yechib olish miqdori: %minimum% %currency%",
            'noChannel' => "To'lovlar kanali ulanmagan!",
            'sendCard' => '<b> Free Fire ID raqamingizni yuboring:</b>',
            'accpeted' => "✅ <b>Qabul qilindi!</b>\n\n• <b>Yechish turi:</b> %wallet%\n• <b>Almaz miqdori:</b> %amount%\n• <b>Free Fire ID raqamingiz:</b> %phone%\n\n<b>Ma'lumotlar to'g'ri ekanligiga ishonch hosil qilgan bo'lsangiz, ✅ Tasdiqlash tugmasini bosing!</b>",
            'solveMoney' => "<b>Qancha miqdorda almaz yechib olmoqchisiz:</b>",
            'solveMinimum' => "<b>Minimal yechib olish miqdori:</b> %minimum% %currency%\n\nQayta urunib ko'ring:",
            'lowBalance' => "<b>Hisobingizda yetarli almaz mavjud emas!</b>\n\nQayta urunib ko'ring:",
            'accped' => '✅ <b>Qabul qilindi.</b>',
            'canceled' => '⛔ <b>Bekor qilindi.</b>',
            'hasBeenPaid' => "<b>Hurmatli %first%!\n\nAlmazlaringizni yechib olish haqidagi arizangiz qabul qilindi.</b>",
            'wasNotPaid' => "<b>Hurmatli %first%!\n\nAlmazlaringizni yechib olish haqidagi arizangiz qabul qilinmadi.</b>",
            'block' => "<b>Hurmatli %first%!\n\nAlmazlaringizni yechib olish haqidagi arizangiz qabul qilinmadi va botdan blocklandingiz.</b>",
            'BeenPaid' => "✅ <a href='tg://user?id=%id%'>%first%</a> <b>foydalanuvchi Almaz yechib oldi.</b>\n\n• <b>Almaz miqdori:</b> %amount% 💎\n• <b>Free Fire ID raqami:</b> %phone%\n\n%advertising%",
            'sendSuppMsg' => ' 📝 <b>Murojaat matnini yuboring:</b>',
            'SuppSend' => "✅ <b>Murojaatingiz yuborildi.</b>\n\nTez orada javob qaytaramiz!",
            'manuals' => "<b>Assalomu aleykum!\n\nSiz Pulga Almaz Sotib olmoqchi bolsangiz zor taklifmz bor 🫡\n\n@Es_madara01 da ALMAZ 💎 ga judayam qaynoq aksiya ketmoqda almaz sotib olsangiz juda tezkor va ishonchli tushadi 😱\n </b>",
            'advertising' => '🤖 <b>Rasmiy botimiz:</b> @%botname%',
        ];
    }
}
