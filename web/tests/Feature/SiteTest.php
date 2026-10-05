<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_public_pages_render(): void
    {
        foreach (['/', '/prices', '/bullion', '/jewelry', '/jewelry/necklace', '/jewelry/bar10', '/sell', '/branches', '/p/faq', '/p/terms', '/p/privacy', '/login'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_home_shows_live_prices_and_no_wallet(): void
    {
        $this->get('/')
            ->assertSee('5,460')
            ->assertSee('اطلب سبيكة')
            ->assertDontSee('محفظ')
            ->assertDontSee('رصيد');
    }

    public function test_shop_filters_by_category_karat_and_branch(): void
    {
        $this->get('/jewelry?c=ring')->assertSee('خاتم سوليتير')->assertDontSee('دبلة كلاسيك');
        $this->get('/jewelry?karat[]=18')->assertSee('حلق دلّاية')->assertDontSee('سلسلة مبرومة بدلاية');

        // the engraved band is only stocked in the third branch
        $branch3 = Branch::where('slug', 'branch-3')->first();
        $this->get('/jewelry?branch='.$branch3->id)->assertSee('دبلة محفورة');
        $branch1 = Branch::where('slug', 'branch-1')->first();
        $this->get('/jewelry?c=band&branch='.$branch1->id)->assertDontSee('دبلة محفورة');
    }

    public function test_unpublished_products_are_hidden(): void
    {
        Product::where('slug', 'necklace')->update(['is_published' => false]);

        $this->get('/jewelry/necklace')->assertNotFound();
        $this->get('/jewelry')->assertDontSee('سلسلة مبرومة بدلاية');
    }
}
