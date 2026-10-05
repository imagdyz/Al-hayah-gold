<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use App\Services\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_logs_in_with_phone_and_otp(): void
    {
        $this->post('/login', ['phone' => '0101 234 5678', 'name' => 'منى'])->assertRedirect('/login/verify');
        $this->assertDatabaseHas('otp_codes', ['phone' => '01012345678']);

        // replace the code with a known one
        OtpCode::query()->delete();
        $code = app(Otp::class)->send('01012345678');

        $this->post('/login/verify', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/login/verify', ['code' => $code])->assertRedirect('/account');

        $this->assertAuthenticated();
        $this->assertSame('منى', User::where('phone', '01012345678')->value('name'));
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $this->post('/login', ['phone' => '12345'])->assertSessionHasErrors('phone');
    }

    public function test_otp_locks_after_too_many_attempts(): void
    {
        $code = app(Otp::class)->send('01012345678');
        foreach (range(1, 5) as $i) {
            $this->assertFalse(app(Otp::class)->verify('01012345678', '111111'));
        }
        $this->assertFalse(app(Otp::class)->verify('01012345678', $code));
    }

    public function test_orders_require_login(): void
    {
        $this->get('/account')->assertRedirect('/login');
        $this->post('/bullion', [])->assertRedirect('/login');
    }
}
