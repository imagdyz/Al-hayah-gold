<?php

namespace Tests\Feature;

use App\Models\GoldPrice;
use App\PriceSources\DaleelakPriceSource;
use App\Services\GoldPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Calls the real getdaleelak.com feed. Skipped unless DALEELAK_LIVE=1, so the
 * normal suite never depends on the network. CI runs it in daleelak-live.yml.
 */
#[Group('live')]
class DaleelakLiveTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        if (! env('DALEELAK_LIVE')) {
            $this->markTestSkipped('Set DALEELAK_LIVE=1 to call the real Daleelak feed.');
        }
        config(['gold.source' => 'daleelak']);
    }

    public function test_feed_has_a_sane_24k_gold_price(): void
    {
        $source = new DaleelakPriceSource(config('gold.daleelak'));
        $assets = $source->goldAssets();

        $this->assertNotNull($assets, 'The feed could not be read.');
        $this->assertNotEmpty($assets, 'The feed has no gold assets.');
        fwrite(STDERR, "\nDaleelak gold assets:\n".json_encode($assets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");

        $a24 = $source->pick24($assets);
        $this->assertNotNull($a24, 'No 24k asset. Slugs: '.implode(', ', array_column($assets, 'slug')));
        $this->assertGreaterThan(1000, $a24['mid'], 'A gram of 24k under 1,000 EGP looks wrong.');
        $this->assertLessThan(100000, $a24['mid'], 'A gram of 24k over 100,000 EGP looks wrong.');

        // 21k should sit near 21/24 of 24k when the feed carries it.
        foreach ($assets as $a) {
            if ($a['mid'] && preg_match('/(^|\D)21(\D|$)/u', $a['slug'].' '.$a['name'])) {
                $ratio = $a['mid'] / $a24['mid'];
                $this->assertEqualsWithDelta(21 / 24, $ratio, 0.05, "21k/24k ratio is {$ratio}");
            }
        }
    }

    public function test_project_runs_on_daleelak_prices_end_to_end(): void
    {
        // Start from no history so the demo seed price does not trip the jump guard.
        GoldPrice::query()->delete();
        app(GoldPricing::class)->forget();

        $this->artisan('prices:refresh')->expectsOutputToContain('Saved 24k base price')->assertSuccessful();

        $latest = GoldPrice::latest('id')->first();
        $this->assertSame('daleelak', $latest->source);

        $base = $latest->base_24;
        $quotes = $this->getJson('/api/v1/prices')->assertOk()->json('quotes');
        fwrite(STDERR, "\nBase 24k from Daleelak: {$base}\nSite quotes: ".json_encode($quotes, JSON_UNESCAPED_UNICODE)."\n");

        $this->assertEqualsWithDelta($base + 25, $quotes['24']['sell'], 5);
        $this->assertEqualsWithDelta($base * 21 / 24 + 22, $quotes['21']['sell'], 5);
        $this->get('/')->assertOk()->assertSee(number_format($quotes['21']['sell']));
    }
}
