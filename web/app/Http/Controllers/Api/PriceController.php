<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoldPricing;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PriceController extends Controller
{
    public function index(GoldPricing $pricing)
    {
        return response()->json($pricing->payload());
    }

    public function history(Request $request, GoldPricing $pricing)
    {
        $data = $request->validate([
            'karat' => ['nullable', Rule::in(GoldPricing::KARATS)],
            'period' => ['nullable', Rule::in(['24h', '7d', '30d'])],
        ]);
        $karat = $data['karat'] ?? '21';
        $period = $data['period'] ?? '7d';

        return response()->json(['karat' => $karat, 'period' => $period, 'points' => $pricing->history($karat, $period)]);
    }
}
