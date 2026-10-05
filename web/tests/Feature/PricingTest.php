<?php

namespace Tests\Feature;

use App\Models\GoldPrice;
use App\Models\Product;
use App\Services\GoldPricing;
use App\Services\Phone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_quotes_follow_the_base_price_and_margins(): void
    {
        $q = app(GoldPricing::class)->quotes();

        $this->assertSame([6240, 6190], [$q['24']['sell'], $q['24']['buy']]);
        $this->assertSame([5460, 5415], [$q['21']['sell'], $q['21']['buy']]);
        $this->assertSame([4680, 4640], [$q['18']['sell'], $q['18']['buy']]);
        $this->assertSame([43680, 43320], [$q['coin']['sell'], $q['coin']['buy']]);
        $this->assertGreaterThan(0, $q['21']['change']);
    }

    public function test_product_price_is_gold_value_plus_making_fee(): void
    {
        $this->assertSame(41800, Product::where('slug', 'necklace')->first()->price());
        $this->assertSame(63100, Product::where('slug', 'bar10')->first()->price());
        $this->assertSame(44300, Product::where('slug', 'coin')->first()->price());
    }

    public function test_a_new_base_price_moves_every_quote(): void
    {
        GoldPrice::create(['base_24' => 6400, 'source' => 'manual', 'recorded_at' => now()->addMinute()]);
        $pricing = app(GoldPricing::class);
        $pricing->forget();

        $this->assertSame(6425, $pricing->sell('24'));
        $this->assertSame(5620, $pricing->sell('21'));
    }

    public function test_phone_numbers_are_normalized(): void
    {
        $this->assertSame('01012345678', Phone::normalize('+20 101 234 5678'));
        $this->assertSame('01012345678', Phone::normalize('٠١٠١٢٣٤٥٦٧٨'));
        $this->assertFalse(Phone::valid(Phone::normalize('0123')));
    }
}
