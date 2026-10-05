<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Otp;
use App\Services\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function create()
    {
        return view('site.auth.login');
    }

    public function send(Request $request, Otp $otp)
    {
        $request->merge(['phone' => Phone::normalize($request->input('phone'))]);
        $data = $request->validate([
            'phone' => ['required', 'regex:'.Phone::PATTERN],
            'name' => ['nullable', 'string', 'max:80'],
        ], ['phone.regex' => 'اكتب رقم موبايل مصري صحيح، 11 رقم يبدأ بـ 01.']);

        $code = $otp->send($data['phone']);
        $request->session()->put('login', ['phone' => $data['phone'], 'name' => $data['name'] ?? null]);

        $status = $code === null ? 'بعتنالك كود من شوية، استنى دقيقة قبل ما تطلب كود جديد.' : 'بعتنالك كود من 6 أرقام.';
        if ($code && config('gold.otp.driver') === 'log' && (app()->isLocal() || config('gold.otp.show_code'))) {
            $status .= " (نسخة تجريبية: الكود {$code})";
        }

        return redirect()->route('login.verify')->with('status', $status);
    }

    public function verifyForm(Request $request)
    {
        $login = $request->session()->get('login');
        if (! $login) {
            return redirect()->route('login');
        }

        return view('site.auth.verify', ['phone' => $login['phone']]);
    }

    public function verify(Request $request, Otp $otp)
    {
        $login = $request->session()->get('login');
        if (! $login) {
            return redirect()->route('login');
        }
        $data = $request->validate(['code' => ['required', 'digits:6']]);

        if (! $otp->verify($login['phone'], $data['code'])) {
            throw ValidationException::withMessages(['code' => 'الكود غلط أو انتهى. اطلب كود جديد لو محتاج.']);
        }

        $user = User::firstOrNew(['phone' => $login['phone']]);
        $user->name = $user->name ?: $login['name'];
        $user->phone_verified_at = now();
        $user->save();

        Auth::login($user, remember: true);
        $request->session()->forget('login');
        $request->session()->regenerate();

        return redirect()->intended(route('account'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
