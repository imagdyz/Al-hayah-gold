<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\GoldPricing;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function admin(): User
    {
        return User::where('is_admin', true)->first();
    }

    public function test_guests_and_customers_are_kept_out(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->actingAs(User::where('is_admin', false)->first())->get('/admin')->assertForbidden();
    }

    public function test_admin_logs_in_with_password(): void
    {
        $this->post('/admin/login', ['email' => 'admin@alhayah.gold', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/admin/login', ['email' => 'admin@alhayah.gold', 'password' => 'password'])->assertRedirect('/admin');
    }

    public function test_admin_pages_render(): void
    {
        $admin = $this->admin();
        foreach (['/admin', '/admin/prices', '/admin/products', '/admin/products/create', '/admin/products/necklace/edit', '/admin/orders', '/admin/orders?status=new', '/admin/branches', '/admin/branches/create'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
        $this->actingAs($admin)->get(route('admin.orders.show', Order::first()))->assertOk();
    }

    public function test_admin_updates_prices_and_halts_trading(): void
    {
        $margins = collect(GoldPricing::KARATS)->mapWithKeys(fn ($k) => [$k => ['sell' => 30, 'buy' => 30]])->all();

        $this->actingAs($this->admin())->put('/admin/prices', [
            'base_24' => 6300, 'margins' => $margins, 'price_lock_minutes' => 15, 'deposit_percent' => 20, 'reservation_hours' => 24,
        ])->assertRedirect();

        $this->getJson('/api/v1/prices')->assertJsonPath('quotes.24.sell', 6330)->assertJsonPath('halted', false);

        $this->actingAs($this->admin())->post('/admin/prices/halt')->assertRedirect();
        $this->getJson('/api/v1/prices')->assertJsonPath('halted', true);
    }

    public function test_cancelling_an_order_returns_the_stock(): void
    {
        $customer = User::where('is_admin', false)->first();
        $bar = Product::where('slug', 'bar5')->first();
        $branch = Branch::where('slug', 'branch-2')->first();
        $q = fn () => (int) DB::table('branch_product')->where('product_id', $bar->id)->where('branch_id', $branch->id)->value('quantity');
        $before = $q();

        $order = app(OrderService::class)->placeBullion($customer, $bar, 3, $branch, 'branch');
        $this->assertSame($before - 3, $q());

        $this->actingAs($this->admin())->patch(route('admin.orders.update', $order), ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame($before, $q());
    }

    public function test_admin_creates_a_product_with_stock(): void
    {
        $branch = Branch::first();
        $this->actingAs($this->admin())->post('/admin/products', [
            'name' => 'خاتم تجربة', 'category' => 'ring', 'karat' => 18, 'weight_g' => 2, 'making_fee' => 500,
            'is_published' => 1, 'stock' => [$branch->id => 3],
        ])->assertRedirect();

        $p = Product::where('name', 'خاتم تجربة')->first();
        $this->assertSame(2 * 4680 + 500, $p->price());
        $this->assertSame(3, (int) $p->branches()->first()->pivot->quantity);
    }
}
