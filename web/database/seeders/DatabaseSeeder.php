<?php

namespace Database\Seeders;

use App\Enums\Category;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Branch;
use App\Models\GoldPrice;
use App\Models\KaratMargin;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Illustrative data that matches the design: placeholder branch names in [ ],
 * example prices and 3D product renders until real photos are taken.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => env('SEED_ADMIN_EMAIL', 'admin@alhayah.gold')], [
            'name' => 'إدارة الحياة جولد',
            'password' => env('SEED_ADMIN_PASSWORD', 'password'),
            'is_admin' => true,
        ]);
        $customer = User::updateOrCreate(['phone' => '01000000001'], ['name' => 'عميل تجريبي', 'phone_verified_at' => now()]);

        $branches = collect([
            ['name' => 'فرع ١', 'area' => '[المنطقة]', 'opens_at' => '10:00', 'closes_at' => '22:00'],
            ['name' => 'فرع ٢', 'area' => '[المنطقة]', 'opens_at' => '10:00', 'closes_at' => '22:00'],
            ['name' => 'فرع ٣', 'area' => '[المنطقة]', 'opens_at' => '12:00', 'closes_at' => '23:00'],
        ])->map(fn ($b, $i) => Branch::updateOrCreate(['slug' => 'branch-'.($i + 1)], $b + [
            'address' => '[العنوان بالتفصيل]',
            'phone' => '[رقم الفرع]',
            'sort' => $i,
        ]));

        foreach (['24' => [25, 25], '21' => [22, 23], '18' => [19, 21], 'coin' => [175, 185]] as $karat => [$sell, $buy]) {
            KaratMargin::updateOrCreate(['karat' => $karat], ['sell_margin' => $sell, 'buy_margin' => $buy]);
        }
        foreach (Setting::DEFAULTS as $key => $value) {
            Setting::put($key, $value);
        }

        $this->seedPrices();

        // name, category, karat, weight, making fee, image, stock per branch, subtitle
        $items = [
            ['سبيكة 1 جرام', Category::Bar, 24, 1, 70, 'bar1', [40, 30, 20], '999.9'],
            ['سبيكة 2.5 جرام', Category::Bar, 24, 2.5, 175, 'bar2_5', [30, 20, 15], '999.9'],
            ['سبيكة 5 جرام', Category::Bar, 24, 5, 350, 'bar5', [30, 20, 15], '999.9'],
            ['سبيكة 10 جرام', Category::Bar, 24, 10, 700, 'bar10', [25, 15, 0], '999.9'],
            ['سبيكة 20 جرام', Category::Bar, 24, 20, 1400, 'bar20', [10, 6, 4], '999.9'],
            ['سبيكة 50 جرام', Category::Bar, 24, 50, 3500, 'bar50', [5, 3, 2], '999.9'],
            ['جنيه ذهب', Category::Coin, 21, 8, 620, 'coin', [40, 30, 25], '8 جرام عيار 21'],
            ['خاتم سوليتير', Category::Ring, 18, 2.8, 1796, 'ring-solitaire', [2, 1, 0], 'ألماظ [القيراط]'],
            ['دبلة كلاسيك', Category::Band, 21, 4.5, 1730, 'band-classic', [4, 3, 2], 'مقاسات [ ]'],
            ['سلسلة مبرومة بدلاية', Category::Necklace, 21, 7.2, 2488, 'necklace', [2, 1, 0], 'طول 45 سم'],
            ['إسورة مضفّرة', Category::Bracelet, 21, 10.5, 3570, 'bracelet-braided', [1, 0, 0], null],
            ['حلق دلّاية', Category::Earring, 18, 3.6, 2252, 'earrings', [2, 0, 1], 'فص زركون'],
            ['طقم ناعم', Category::Set, 21, 14, 5060, 'set', [0, 1, 0], 'سلسلة وحلق'],
            ['خاتم فصوص', Category::Ring, 18, 3.4, 1688, 'ring-stones', [1, 1, 0], '7 فصوص'],
            ['دبلة محفورة', Category::Band, 21, 5.2, 1708, 'band-engraved', [0, 0, 2], null],
            ['إسورة سلسلة', Category::Bracelet, 21, 6.8, 2272, 'bracelet-chain', [2, 1, 1], null],
        ];

        $descriptions = [
            Category::Bar->value => 'سبيكة عيار 24 (999.9) مختومة بختم الحياة جولد ورقم تسلسلي، في غلاف محكم ومعاها فاتورة.',
            Category::Coin->value => 'جنيه ذهب عيار 21 وزنه 8 جرام، بختم الحياة جولد.',
        ];

        foreach ($items as $i => [$name, $category, $karat, $weight, $making, $image, $stock, $subtitle]) {
            $product = Product::updateOrCreate(['slug' => Str::slug($image)], [
                'name' => $name,
                'category' => $category,
                'karat' => $karat,
                'weight_g' => $weight,
                'making_fee' => $making,
                'sku' => 'AH-'.str_pad((string) ($i + 101), 4, '0', STR_PAD_LEFT),
                'subtitle' => $subtitle,
                'description' => $descriptions[$category->value] ?? '[وصف القطعة من الفرع]',
                'image' => "images/products/{$image}.webp",
                'is_published' => true,
                'is_featured' => ! $category->isBullion(),
                'sort' => $i,
            ]);
            $product->branches()->sync($branches->mapWithKeys(fn ($b, $j) => [$b->id => ['quantity' => $stock[$j]]])->all());
        }

        $this->seedOrders($customer, $branches->all());
    }

    private function seedPrices(): void
    {
        GoldPrice::query()->delete();
        mt_srand(7);
        $hours = 30 * 24;
        $rows = [];
        for ($i = 0; $i <= $hours; $i++) {
            $v = 5950 + (6141 - 5950) * min($i / ($hours - 24), 1) ** 1.6 + 18 * sin($i / 9) + 9 * sin($i / 3.3) + mt_rand(-6, 6);
            if ($i >= $hours - 24) {
                // the last day climbs about 1.2% to today's price
                $v = 6141 + (6215 - 6141) * ($i - ($hours - 24)) / 24 + 4 * sin($i / 2.1);
            }
            $rows[] = [
                'base_24' => $i === $hours ? 6215 : round($v, 2),
                'source' => 'seed',
                'recorded_at' => now()->startOfHour()->subHours($hours - $i),
            ];
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            GoldPrice::insert($chunk);
        }
    }

    private function seedOrders(User $customer, array $branches): void
    {
        if (Order::exists()) {
            return;
        }
        mt_srand(11);
        $products = Product::all();
        $statuses = [OrderStatus::Completed, OrderStatus::Completed, OrderStatus::Ready, OrderStatus::Confirmed, OrderStatus::New, OrderStatus::Cancelled];
        for ($d = 13; $d >= 0; $d--) {
            $n = 2 + ($d * 7 + 3) % 5;
            for ($k = 0; $k < $n; $k++) {
                $type = [OrderType::Bullion, OrderType::Bullion, OrderType::Reservation, OrderType::Sell][mt_rand(0, 3)];
                $product = $type === OrderType::Bullion ? $products->filter->isBullion()->random() : $products->reject->isBullion()->random();
                $status = $d > 2 ? $statuses[mt_rand(0, 2)] : $statuses[mt_rand(2, 5)];
                $created = now()->subDays($d)->setTime(10 + mt_rand(0, 10), mt_rand(0, 59));
                $qty = $type === OrderType::Bullion ? mt_rand(1, 3) : 1;
                $unit = $type === OrderType::Sell ? 5415 : $product->price();
                $grams = $type === OrderType::Sell ? mt_rand(4, 30) : (float) $product->weight_g * $qty;
                Order::create([
                    'code' => Order::newCode(),
                    'type' => $type,
                    'status' => $status,
                    'user_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'phone' => '010'.mt_rand(10000000, 99999999),
                    'branch_id' => $branches[mt_rand(0, 2)]->id,
                    'product_id' => $type === OrderType::Sell ? null : $product->id,
                    'quantity' => $qty,
                    'karat' => $type === OrderType::Sell ? 21 : $product->karat,
                    'weight_g' => $grams,
                    'unit_price' => $unit,
                    'total' => $type === OrderType::Sell ? $unit * $grams : $unit * $qty,
                    'pay_method' => mt_rand(0, 1) ? 'branch' : 'deposit',
                    'locked_until' => $created->copy()->addMinutes(30),
                    'slot_at' => $type === OrderType::Bullion ? null : $created->copy()->addDay()->setTime(13, 0),
                ])->forceFill(['created_at' => $created, 'updated_at' => $created])->save();
            }
        }
    }
}
