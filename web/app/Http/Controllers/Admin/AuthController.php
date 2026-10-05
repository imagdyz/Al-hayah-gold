<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function create()
    {
        return view('admin.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        if (! Auth::attempt($credentials + ['is_admin' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'الإيميل أو كلمة السر مش صح.']);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }
}
