<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public const KEYS = ['shop_name', 'shop_tagline', 'shop_address', 'shop_phone', 'invoice_start'];

    public function edit()
    {
        return view('admin.shop.edit', [
            'values' => collect(self::KEYS)->mapWithKeys(fn ($k) => [$k => Setting::get($k)]),
            'lastNumber' => Invoice::query()->max('number'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'shop_name' => ['required', 'string', 'max:60'],
            'shop_tagline' => ['nullable', 'string', 'max:80'],
            'shop_address' => ['nullable', 'string', 'max:120'],
            'shop_phone' => ['nullable', 'string', 'max:40'],
            'invoice_start' => ['required', 'integer', 'min:1', 'max:99999999'],
        ]);

        foreach (self::KEYS as $key) {
            Setting::put($key, (string) ($data[$key] ?? ''));
        }

        return back()->with('status', 'بيانات المحل اتحفظت.');
    }
}
