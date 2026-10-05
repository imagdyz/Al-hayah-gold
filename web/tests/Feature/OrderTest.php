<?php

namespace Tests\Feature;

use App\Http\Controllers\Site\ReservationController;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function stock(Product $p, Branch $b): int
    {
        return (int) DB::table('branch_product')->where('product_id', $p->id)->where('branch_id', $b->id)->value('quantity');
    }

    private function customer(): User
    {
        return User::factory()->create(['phone' => '01011112222', 'email' => null]);
    }

    public function test_bullion_order_locks_price_and_holds_stock(): void
    {
        $user = $this->customer();
        $bar = Product::where('slug', 'bar10')->first();
        $branch = Branch::where('slug', 'branch-1')->first();
        $before = $this->stock($bar, $branch);

        $res = $this->actingAs($user)->post('/bullion', [
            'product_id' => $bar->id, 'quantity' => 2, 'branch_id' => $branch->id, 'pay_method' => 'deposit',
        ]);

        $order = Order::where('user_id', $user->id)->latest('id')->first();
        $res->assertRedirect(route('orders.show', $order));
        $this->assertSame(126200.0, (float) $order->total);
        $this->assertSame(12620.0, (float) $order->deposit);
        $this->assertNotNull($order->locked_until);
        $this->assertSame($before - 2, $this->stock($bar, $branch));

        $this->actingAs($user)->get(route('orders.show', $order))->assertOk()->assertSee($order->code)->assertSee('<svg', false);
    }

    public function test_out_of_stock_branch_is_refused(): void
    {
        $bar = Product::where('slug', 'bar10')->first();
        $branch3 = Branch::where('slug', 'branch-3')->first(); // seeded with 0

        $this->actingAs($this->customer())->post('/bullion', [
            'product_id' => $bar->id, 'quantity' => 1, 'branch_id' => $branch3->id, 'pay_method' => 'branch',
        ])->assertSessionHasErrors('branch_id');

        $this->assertSame(0, Order::where('phone', '01011112222')->count());
    }

    public function test_halted_trading_blocks_orders(): void
    {
        Setting::put('trading_halted', true);
        $bar = Product::where('slug', 'bar1')->first();

        $this->actingAs($this->customer())->post('/bullion', [
            'product_id' => $bar->id, 'quantity' => 1, 'branch_id' => Branch::first()->id, 'pay_method' => 'branch',
        ])->assertSessionHasErrors('trading');
    }

    public function test_reservation_and_sell_appointment(): void
    {
        $user = $this->customer();
        $necklace = Product::where('slug', 'necklace')->first();
        $branch = Branch::where('slug', 'branch-1')->first();
        $slot = array_key_first(ReservationController::slots());

        $this->actingAs($user)->post("/jewelry/{$necklace->slug}/reserve", [
            'branch_id' => $branch->id, 'slot' => $slot, 'pay_method' => 'branch',
        ])->assertRedirect();
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'type' => 'reservation', 'product_id' => $necklace->id]);

        $this->actingAs($user)->post('/sell', [
            'karat' => '21', 'grams' => 12, 'branch_id' => $branch->id, 'slot' => $slot,
        ])->assertRedirect();
        $sell = Order::where('user_id', $user->id)->where('type', 'sell')->first();
        $this->assertSame(12 * 5415.0, (float) $sell->total);

        $this->actingAs($user)->get('/account')->assertOk()->assertSee('سلسلة مبرومة بدلاية')->assertSee('بيع ذهب عيار 21');
    }

    public function test_customers_cannot_see_each_others_orders(): void
    {
        $order = Order::first();
        $this->actingAs($this->customer())->get(route('orders.show', $order))->assertNotFound();
    }
}
