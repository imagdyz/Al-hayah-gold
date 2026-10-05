# الحياة جولد: الموقع ولوحة التحكم والـ API

مشروع Laravel 13 فيه:
- الموقع (Blade + Tailwind v4 + Alpine.js).
- لوحة تحكم الفروع.
- API للتطبيق (`/api/v1`، Sanctum).

## التشغيل محلياً

```bash
cd web
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed        # بيانات توضيحية مطابقة للتصميم
php artisan storage:link          # لصور المنتجات المرفوعة من لوحة التحكم
npm install && npm run build      # أو npm run dev وقت التطوير
php artisan serve
```

- **الموقع:** http://localhost:8000
- **لوحة التحكم:** http://localhost:8000/admin
  - الإيميل: `admin@alhayah.gold`
  - كلمة السر: `password`
  - **غيّرها قبل أي نشر.** تقدر تحددها وقت الـ seed بـ `SEED_ADMIN_EMAIL` و `SEED_ADMIN_PASSWORD`.
- **دخول العملاء:** برقم الموبايل وكود OTP.
  - في وضع التطوير (`OTP_DRIVER=log`) الكود بيتكتب في `storage/logs/laravel.log`، وبيظهر في رسالة الصفحة كمان.

## الاختبارات

```bash
php artisan test
./vendor/bin/pint --test
```

## إزاي الأسعار بتشتغل

**مصدر السعر:**
- `gold_prices` بيسجّل سعر جرام 24 الأساسي (السعر العالمي بالجنيه) مع الوقت.
- المصدر بيتحدد بـ `GOLD_PRICE_SOURCE`:
  - `manual`: الأدمن بيدخّل السعر من صفحة الأسعار.
  - `daleelak`: [دليلك](https://getdaleelak.com/ar/api)، feed مجاني من غير key.
    - **السعر الأساسي:** متوسط أفضل شراء وأفضل بيع لعيار 24، عشان هامشنا ما يتحسبش فوق هامش محل.
    - **أصل عيار 24:** بيتحدد بـ `DALEELAK_ASSET_24`.
    - **قبل التشغيل:** شغّل `php artisan prices:probe`. بيعرض أصول الذهب والسعر اللي هيتاخد، من غير ما يحفظ حاجة.
  - `http`: أي API بيرجّع سعر جرام 24 بالجنيه، زي goldapi.io (`XAU/EGP`، المسار `price_gram_24k`). بتحط الإعدادات في `GOLD_PRICE_URL` و `GOLD_PRICE_TOKEN` و `GOLD_PRICE_PATH`.
  - في وضع `http`، الأمر `php artisan prices:refresh` بيشتغل كل دقيقة من الـ scheduler (`php artisan schedule:work`، أو cron على `schedule:run`).

**حماية المصادر التلقائية** (`App\Services\PriceFeed`):
- **قفزة كبيرة:** لو السعر الجديد بعيد عن آخر سعر بأكتر من `GOLD_MAX_JUMP_PERCENT` (افتراضي 3%)، ما بيتسجّلش والطلبات أونلاين بتقف.
- **سعر قديم:** لو مفيش سعر جديد من `GOLD_STALE_MINUTES` دقيقة (افتراضي 15)، `prices:check-stale` بيوقف الطلبات.
- **السبب:** بيظهر في صفحة الأسعار في لوحة التحكم، ومن هناك الأدمن يرجّع الطلبات.

**الحساب** (`App\Services\GoldPricing`):
- **الأعيرة:** 21 = الأساسي × 21/24، و 18 = الأساسي × 18/24.
- **الجنيه:** 8 جم عيار 21.
- **الهوامش:** هامش البيع والشراء لكل عيار من `karat_margins`.
- **التقريب:** لأقرب 5 جنيه.
- **سعر المنتج:** الوزن × سعر العيار + المصنعية.

**إيقاف الطلبات:**
- زرار "وقّف الطلبات أونلاين" في لوحة التحكم.
- بيمنع أي طلب سبائك أو حجز جديد، والأسعار بتفضل ظاهرة.

## الطلبات (مفيش محفظة)

| النوع | إيه اللي بيحصل |
| --- | --- |
| `bullion` | طلب سبيكة أو جنيه بسعر مثبّت `price_lock_minutes` دقيقة، والاستلام من الفرع. الكمية بتتحجز من مخزون الفرع. |
| `reservation` | حجز قطعة مجوهرات في فرع لمدة `reservation_hours` ساعة، والدفع في الفرع أو بعربون. |
| `sell` | معاد في الفرع لبيع الذهب القديم أو تبديله، بقيمة تقديرية على سعر الشراء. |

لو الطلب اتلغى من لوحة التحكم، الكمية بترجع للمخزون.

> **العربون أونلاين:** محتاج بوابة دفع (Paymob أو Fawry). الطلب دلوقتي بيتسجّل بقيمة العربون، والدفع نفسه هيتفعّل لما يتعمل حساب تاجر.

## الـ API للتطبيق

| Method | المسار | ملاحظات |
| --- | --- | --- |
| GET | `/api/v1/prices` | الأسعار الحالية، وتغيّر 24 ساعة، وحالة الإيقاف |
| GET | `/api/v1/prices/history?karat=21&period=7d` | `24h` / `7d` / `30d` |
| GET | `/api/v1/products?filter=bullion&karat=21&branch=1` | المنتجات بالسعر والمخزون |
| GET | `/api/v1/products/{slug}` | |
| GET | `/api/v1/branches` | |
| POST | `/api/v1/auth/otp` | `{phone}` |
| POST | `/api/v1/auth/verify` | `{phone, code, name?, device_name?}` ← `token` |
| GET | `/api/v1/orders` | `Authorization: Bearer <token>` |
| POST | `/api/v1/orders` | `type`: `bullion` / `reservation` / `sell` |

## قبل الإطلاق

- [ ] بيانات الفروع الحقيقية والمخزون وتصوير المنتجات. الصور الحالية 3D توضيحية، من `tools/renders`.
- [ ] مصدر السعر اللحظي، والهوامش، والمصنعية.
- [ ] مزوّد SMS أو WhatsApp للـ OTP. ده بيتربط في `App\Services\Otp`.
- [ ] بوابة الدفع للعربون.
- [ ] الشروط والأحكام وسياسة الخصوصية بعد المراجعة القانونية.
- [ ] MySQL في الإنتاج، و `APP_DEBUG=false`، و HTTPS، وتغيير كلمة سر الأدمن.
