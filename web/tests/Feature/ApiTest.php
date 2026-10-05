<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\OtpCode;
use App\Services\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_public_endpoints(): void
    {
        $this->getJson('/api/v1/prices')->assertOk()->assertJsonPath('quotes.21.sell', 5460);
        $this->getJson('/api/v1/prices/history?karat=coin&period=24h')->assertOk()->assertJsonStructure(['points' => [['t', 'v']]]);
        $this->getJson('/api/v1/products?filter=bullion')->assertOk()->assertJsonCount(7, 'data');
        $this->getJson('/api/v1/products/necklace')->assertOk()->assertJsonPath('data.price', 41800);
        $this->getJson('/api/v1/branches')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_app_login_and_order(): void
    {
        $this->postJson('/api/v1/auth/otp', ['phone' => '01055556666'])->assertOk();
        OtpCode::query()->delete();
        $code = app(Otp::class)->send('01055556666');

        $token = $this->postJson('/api/v1/auth/verify', ['phone' => '01055556666', 'code' => $code])->assertOk()->json('token');
        $auth = ['Authorization' => 'Bearer '.$token];

        $this->getJson('/api/v1/me', $auth)->assertOk()->assertJsonPath('phone', '01055556666');
        $this->postJson('/api/v1/orders', [
            'type' => 'bullion', 'product' => 'coin', 'quantity' => 1, 'branch_id' => Branch::first()->id,
        ], $auth)->assertCreated()->assertJsonPath('data.total', 44300);

        $this->getJson('/api/v1/orders', $auth)->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_orders_need_a_token(): void
    {
        $this->getJson('/api/v1/orders')->assertUnauthorized();
    }
}
