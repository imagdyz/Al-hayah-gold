<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Otp;
use App\Services\Phone;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function send(Request $request, Otp $otp)
    {
        $request->merge(['phone' => Phone::normalize($request->input('phone'))]);
        $data = $request->validate(['phone' => ['required', 'regex:'.Phone::PATTERN]], ['phone.regex' => 'رقم الموبايل لازم يكون 11 رقم ويبدأ بـ 01.']);

        $sent = $otp->send($data['phone']) !== null;

        return response()->json(['sent' => $sent, 'retry_after' => $sent ? config('gold.otp.resend_seconds') : null], $sent ? 200 : 429);
    }

    public function verify(Request $request, Otp $otp)
    {
        $request->merge(['phone' => Phone::normalize($request->input('phone'))]);
        $data = $request->validate([
            'phone' => ['required', 'regex:'.Phone::PATTERN],
            'code' => ['required', 'digits:6'],
            'name' => ['nullable', 'string', 'max:80'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        if (! $otp->verify($data['phone'], $data['code'])) {
            throw ValidationException::withMessages(['code' => 'الكود غلط أو انتهى.']);
        }

        $user = User::firstOrNew(['phone' => $data['phone']]);
        $user->name = $user->name ?: ($data['name'] ?? null);
        $user->phone_verified_at = now();
        $user->save();

        return response()->json([
            'token' => $user->createToken($data['device_name'] ?? 'app')->plainTextToken,
            'user' => ['id' => $user->id, 'name' => $user->name, 'phone' => $user->phone],
        ]);
    }

    public function me(Request $request)
    {
        $u = $request->user();

        return response()->json(['id' => $u->id, 'name' => $u->name, 'phone' => $u->phone]);
    }
}
