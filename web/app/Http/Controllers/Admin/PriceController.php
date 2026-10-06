<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoldPrice;
use App\Models\KaratMargin;
use App\Models\Setting;
use App\Services\GoldPricing;
use Illuminate\Http\Request;

class PriceController extends Controller
{
    public function edit(GoldPricing $pricing)
    {
        return view('admin.prices', [
            'base' => $pricing->base(),
            'updatedAt' => $pricing->updatedAt(),
            'source' => config('gold.source'),
            'quotes' => $pricing->quotes(),
            'margins' => KaratMargin::all()->keyBy('karat'),
            'settings' => [
                'price_lock_minutes' => Setting::int('price_lock_minutes'),
                'deposit_percent' => Setting::int('deposit_percent'),
                'reservation_hours' => Setting::int('reservation_hours'),
            ],
            'halted' => $pricing->halted(),
            'haltReason' => Setting::get('halt_reason'),
            'recent' => GoldPrice::latest('recorded_at')->take(6)->get(),
        ]);
    }

    public function update(Request $request, GoldPricing $pricing)
    {
        $data = $request->validate([
            'base_24' => ['nullable', 'numeric', 'min:100', 'max:1000000'],
            'margins' => ['required', 'array'],
            'margins.*.sell' => ['required', 'numeric', 'min:0', 'max:100000'],
            'margins.*.buy' => ['required', 'numeric', 'min:0', 'max:100000'],
            'price_lock_minutes' => ['required', 'integer', 'between:1,1440'],
            'deposit_percent' => ['required', 'integer', 'between:0,100'],
            'reservation_hours' => ['required', 'integer', 'between:1,336'],
        ]);

        if (! empty($data['base_24']) && (float) $data['base_24'] !== $pricing->base()) {
            GoldPrice::create(['base_24' => $data['base_24'], 'source' => 'manual', 'recorded_at' => now()]);
        }
        foreach (GoldPricing::KARATS as $k) {
            if (isset($data['margins'][$k])) {
                KaratMargin::updateOrCreate(['karat' => $k], ['sell_margin' => $data['margins'][$k]['sell'], 'buy_margin' => $data['margins'][$k]['buy']]);
            }
        }
        foreach (['price_lock_minutes', 'deposit_percent', 'reservation_hours'] as $key) {
            Setting::put($key, $data[$key]);
        }

        $pricing->forget();

        return back()->with('status', 'الأسعار اتحدّثت في الموقع والتطبيق.');
    }

    public function toggleHalt(GoldPricing $pricing)
    {
        $halt = ! $pricing->halted();
        Setting::put('trading_halted', $halt);
        Setting::put('halt_reason', $halt ? 'وقفها الأدمن يدوياً.' : '');
        Setting::put('halt_kind', $halt ? 'admin' : '');

        return back()->with('status', $halt ? 'وقفنا الطلبات أونلاين. الأسعار بتظهر للعرض بس.' : 'رجّعنا الطلبات أونلاين.');
    }
}
