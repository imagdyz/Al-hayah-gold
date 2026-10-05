<?php

namespace App\Services;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class Otp
{
    /** Returns the plain code (for the log driver and tests), or null when asked to wait. */
    public function send(string $phone): ?string
    {
        $recent = OtpCode::where('phone', $phone)
            ->where('created_at', '>', now()->subSeconds(config('gold.otp.resend_seconds')))
            ->exists();
        if ($recent) {
            return null;
        }

        $code = (string) random_int(100000, 999999);
        OtpCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(config('gold.otp.ttl_minutes')),
        ]);

        match (config('gold.otp.driver')) {
            // Hook an SMS provider in here (e.g. a WhatsApp/SMS gateway client).
            default => Log::info("OTP for {$phone}: {$code}"),
        };

        return $code;
    }

    public function verify(string $phone, string $code): bool
    {
        $otp = OtpCode::where('phone', $phone)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $otp || $otp->attempts >= config('gold.otp.max_attempts')) {
            return false;
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }
}
