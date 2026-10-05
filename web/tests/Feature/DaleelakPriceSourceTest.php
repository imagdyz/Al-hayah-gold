<?php

namespace Tests\Feature;

use App\Models\GoldPrice;
use App\Models\Setting;
use App\PriceSources\DaleelakPriceSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Runs against a fixture shaped like the documented feed. Once the real feed is
 * reachable, check it with `php artisan prices:probe` and refresh the fixture.
 */
class DaleelakPriceSourceTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const URL = 'https://getdaleelak.com/api/v1/feed.json*';

    protected function setUp(): void
    {
        parent::setUp();
        config(['gold.source' => 'daleelak']);
    }

    private function feed(): array
    {
        return json_decode(file_get_contents(base_path('tests/Fixtures/daleelak-feed.json')), true);
    }

    private function source(): DaleelakPriceSource
    {
        return new DaleelakPriceSource(config('gold.daleelak'));
    }

    public function test_base_price_is_the_mid_of_best_buy_and_sell(): void
    {
        Http::fake([self::URL => Http::response($this->feed(), 200, ['ETag' => '"v1"'])]);

        $this->assertSame(6217.0, $this->source()->fetchBase24());
        Http::assertSent(fn ($r) => str_contains($r->url(), 'category=metals'));
    }

    public function test_refresh_saves_the_price_and_updates_the_quotes(): void
    {
        Http::fake([self::URL => Http::response($this->feed())]);

        $this->artisan('prices:refresh')->expectsOutputToContain('Saved 24k base price: 6217')->assertSuccessful();

        $this->assertSame('daleelak', GoldPrice::latest('id')->value('source'));
        // 6217 + 25 margin, rounded to 5
        $this->getJson('/api/v1/prices')->assertJsonPath('quotes.24.sell', 6240)->assertJsonPath('quotes.24.buy', 6190);
    }

    public function test_etag_is_sent_back_and_304_means_nothing_new(): void
    {
        Http::fake([self::URL => Http::sequence()->push($this->feed(), 200, ['ETag' => '"v1"'])->push('', 304)]);

        $this->assertNotNull($this->source()->fetchBase24());
        $this->assertNull($this->source()->fetchBase24());
        Http::assertSent(fn ($r) => $r->hasHeader('If-None-Match', '"v1"'));
    }

    public function test_rate_limits_and_errors_do_not_save_or_throw(): void
    {
        $count = GoldPrice::count();
        foreach ([Http::response('', 429, ['Retry-After' => '30']), Http::response('oops', 500), Http::response('not json', 200)] as $response) {
            Http::fake([self::URL => $response]);
            $this->artisan('prices:refresh')->expectsOutputToContain('No new price')->assertSuccessful();
        }
        $this->assertSame($count, GoldPrice::count());
    }

    public function test_falls_back_to_an_asset_named_24(): void
    {
        $feed = $this->feed();
        $feed['data']['assets'][0]['slug'] = 'egypt-gold-24';
        Http::fake([self::URL => Http::response($feed)]);

        $this->assertSame(6217.0, $this->source()->fetchBase24());
    }

    public function test_a_feed_without_24k_gives_no_price(): void
    {
        $feed = $this->feed();
        array_shift($feed['data']['assets']);
        Http::fake([self::URL => Http::response($feed)]);

        $this->assertNull($this->source()->fetchBase24());
    }

    public function test_a_big_jump_is_rejected_and_halts_orders(): void
    {
        $feed = $this->feed();
        $feed['data']['assets'][0]['directions'] = ['buy' => ['best' => 7000], 'sell' => ['best' => 6990]];
        Http::fake([self::URL => Http::response($feed)]);
        $count = GoldPrice::count();

        $this->artisan('prices:refresh')->expectsOutputToContain('Online orders halted')->assertSuccessful();

        $this->assertSame($count, GoldPrice::count());
        $this->assertTrue(Setting::bool('trading_halted'));
        $this->assertStringContainsString('daleelak', Setting::get('halt_reason'));
    }

    public function test_stale_prices_halt_orders_for_automatic_sources_only(): void
    {
        GoldPrice::query()->update(['recorded_at' => now()->subHour()]);

        config(['gold.source' => 'manual']);
        $this->artisan('prices:check-stale')->assertSuccessful();
        $this->assertFalse(Setting::bool('trading_halted'));

        config(['gold.source' => 'daleelak']);
        $this->artisan('prices:check-stale')->expectsOutputToContain('halted')->assertSuccessful();
        $this->assertTrue(Setting::bool('trading_halted'));
    }

    public function test_probe_lists_gold_assets_without_saving(): void
    {
        Http::fake([self::URL => Http::response($this->feed())]);
        $count = GoldPrice::count();

        $this->artisan('prices:probe')->expectsOutputToContain('24k asset: gold-24k')->assertSuccessful();
        $this->assertSame($count, GoldPrice::count());
    }

    public function test_silver_in_the_metals_feed_is_ignored(): void
    {
        Http::fake([self::URL => Http::response($this->feed())]);

        $slugs = array_column($this->source()->goldAssets(), 'slug');

        $this->assertSame(['gold-24k', 'gold-21k', 'gold-pound'], $slugs);
    }
}
