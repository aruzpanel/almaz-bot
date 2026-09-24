# Almaz-bot (zamonaviylashtirilgan versiya)

Bu — `Downloads\bot.php` faylining PHP 8 + SQLite (PDO) asosidagi, OOP tuzilishga ega qayta yozilgan versiyasi. Asl bot fayl-asosli saqlashdan (`pul/`, `odam/`, `ban/`, `step/`, `admin/*.txt`, ...) foydalangan; bu versiya bitta `data/bot.sqlite` bazasidan foydalanadi.

> **Eslatma:** kod PDO orqali yozilgan, shuning uchun boshqa bazaga (masalan MySQL) o'tish printsipial jihatdan oson, lekin `schema.sql` va repository'lardagi bir nechta SQLite-ga xos ibora (`INSERT OR IGNORE`, `ON CONFLICT ... DO UPDATE`, `PRAGMA`) MySQL uchun mos ravishda o'zgartirilishi kerak bo'ladi (`INSERT IGNORE`, `ON DUPLICATE KEY UPDATE`). Hozircha standart va tavsiya etilgan variant — SQLite.

## Tuzilma

```
almaz-bot/
  public/index.php         Telegram shu yerga POST qiladi (veb-ildiz = public/)
  migrate_from_legacy.php  eski fayllardan bazaga bir martalik import
  bootstrap.php            konfiguratsiya + bog'lovchi (DI) qurilmasi
  schema.sql                baza sxemasi
  .env.example              nusxalab .env qiling va to'ldiring
  src/
    Config.php, Database.php, TelegramClient.php, Update.php, Context.php, Router.php, Defaults.php
    Entity/User.php
    Repository/            har bir jadval uchun CRUD klasslar
    Services/               SubscriptionGate (majburiy obuna), ReferralService (referral bonusi)
    Support/                Keyboards, Templates, Labels, Menus
    Handlers/               har bir bo'lim uchun alohida klass (Onboarding, MainMenu, Withdrawal,
                             AdminPanel, AdminSettings, AdminChannels, AdminPaymentMethods,
                             AdminUsers, AdminBroadcast, AdminContentEditor)
```

## O'rnatish

1. `.env.example` faylini `.env` deb nusxalang va to'ldiring:
   - `BOT_TOKEN` — BotFather bergan token
   - `ADMIN_ID` — asosiy admin Telegram ID
   - `WEBHOOK_SECRET` — o'zingiz o'ylab topgan uzun tasodifiy satr (Telegram webhook so'rovlarini tasdiqlash uchun)
   - `DB_PATH` — SQLite fayli joylashuvi (standart: `data/bot.sqlite`)
2. Serverga `almaz-bot/` papkasini yuklang (PHP 8.1+, `pdo_sqlite` va `curl` kengaytmalari yoqilgan bo'lishi kerak).
3. `data/` papkasiga yozish huquqi berilganini tekshiring.

## Eski ma'lumotlarni ko'chirish

Agar productionda ishlab turgan eski botdan o'tkazayotgan bo'lsangiz:

```bash
php migrate_from_legacy.php /path/to/old-bot-folder
```

Bu skript eski `pul/`, `odam/`, `ban/`, `number/`, `admin/*.txt`, `admin/kanal.txt`, `number/turi.txt`, `matn/*.txt`, `tugma/*.txt`, `azo.dat` fayllarini o'qib yangi bazaga yozadi (adminning matn/tugma bo'yicha kiritgan o'zgarishlari ham saqlanadi). Qayta ishga tushirish xavfsiz.

## Tekshirish (bu muhitda PHP bo'lmagani uchun o'zingiz bajarishingiz kerak)

1. Sintaksis tekshiruvi: `php -l public/index.php` va har bir `src/**/*.php` fayl uchun (yoki `find src -name "*.php" -exec php -l {} \;`).
2. **Yangi, alohida test bot** yarating (@BotFather → `/newbot`), production tokenidan foydalanmang.
3. Lokal serverni ko'taring: `php -S localhost:8000 -t public`.
4. `ngrok http 8000` (yoki shunga o'xshash) orqali tashqi manzil oling va test botingizga shu manzilni webhook qilib bering:
   ```bash
   curl "https://api.telegram.org/bot<TEST_TOKEN>/setWebhook?url=https://<ngrok-manzil>/&secret_token=<.env dagi WEBHOOK_SECRET>"
   ```
5. Test botida qo'lda tekshiring: `/start`, referral havolasi (`/start <biror-id>`), telefon yuborish, majburiy obuna gate, "Almaz ishlash", kabinet, pul yechish so'rovi (usul tanlash → FF ID → miqdor → tasdiqlash → admin ko'rib chiqishi), va admin panelning barcha bo'limlari (sozlamalar, kanallar, to'lov usullari, xabarnoma, foydalanuvchi boshqarish, tugma/matn tahrirlash).
6. Faqat shundan keyin production tokeni bilan webhookni yangi kodga yo'naltiring:
   ```bash
   curl "https://api.telegram.org/bot<PROD_TOKEN>/setWebhook?url=https://sizning-domeningiz/&secret_token=<WEBHOOK_SECRET>"
   ```
   Eski `bot.php` faylini **o'chirmang** — muammo chiqsa, webhookni eski manzilga qaytarib qo'yish orqali darhol orqaga qaytarish mumkin.

## Asl koddan farqlar

**Xavfsizlik/tuzatishlar:**
- Bot tokeni va admin ID endi `.env`da, kodda emas.
- Webhookka `X-Telegram-Bot-Api-Secret-Token` tekshiruvi qo'shildi.
- Foydalanuvchi yozgan erkin matn (murojaat, username) HTML xabarlarga qo'yishdan oldin escape qilinadi.
- Balansni o'zgartirish endi atomik SQL orqali (fayllarni o'qib-yozish o'rniga).
- Telegram API chaqiruvlarida timeout va xatolarni `error_log`ga yozish qo'shildi.
- Ruxsat etilgan telefon kodlari (998, 996) `.env`ga chiqarildi.
- Referral tizimidagi ikkita nozik xato tabiiy ravishda tuzatildi: (1) hech qanday referali bo'lmagan foydalanuvchiga tasodifiy bonus berilish xavfi (eski kodda `file_get_contents` fayl topilmaganda `false` qaytarishiga asoslangan solishtirish xatosi bor edi), (2) referral havolasi orqali qaytadan kirgan oddiy (admin bo'lmagan) foydalanuvchiga noto'g'ri admin-klaviaturasi ko'rsatilishi.
- "◀️ Orqaga" tugmasi eski kodda ikki marta (ikkita mos keluvchi blok orqali) ishlov berilib, ketma-ket ikkita bir xil xabar yuborar edi — endi bitta, aniq handler.

**Olib tashlangan (kelishilganidek):**
- Hech qaysi menyuda ko'rinmagan va aniqlanmagan `$adxambek` o'zgaruvchisiga murojaat qilib xato beradigan "📋 Vazifalar" (Instagram vazifalari) bo'limi va uning `on=`/`off=` tasdiqlash callback'lari.
- Hech qaysi menyuda ko'rinmagan "🔒" va soxta progress-bar "⚒️Botni Yangilash" bo'limlari.

**O'zgartirilgan:**
- "⚙️PULLI NASTROYKA⚙️" tugmasi endi `@Es_madara01`ga ulanadi (avval shablon sotuvchisining `@ZufarMaxOrg` havolasi edi).

**Ichki (foydalanuvchiga ko'rinmaydigan) yaxshilanish:**
- Pul yechish so'rovlari endi `withdrawals` jadvalida to'liq audit-iz bilan saqlanadi (eski versiyada bu ma'lumot faqat vaqtinchalik callback tugmalari ichida yashardi, hech qayerda yozilmasdi).
- Admin tasdiqlash tugmalari endi `wallet`/`FF ID`/`miqdor` qiymatlarini chiziqcha (`-`) bilan callback_data ichiga joylashtirish o'rniga bazadagi so'rov ID'siga ishora qiladi — usul nomida chiziqcha bo'lsa ham noto'g'ri parslanish xavfi yo'q.
