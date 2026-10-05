<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\GoldPricing;
use Illuminate\Http\Request;

class PriceController extends Controller
{
    public function index(Request $request, GoldPricing $pricing)
    {
        $karat = in_array($request->query('karat'), GoldPricing::KARATS, true) ? $request->query('karat') : '21';
        $period = in_array($request->query('period'), ['24h', '7d', '30d'], true) ? $request->query('period') : '7d';

        $series = [];
        foreach (GoldPricing::KARATS as $k) {
            foreach (['24h', '7d', '30d'] as $p) {
                $series[$k][$p] = $pricing->history($k, $p);
            }
        }

        return view('site.prices', compact('series', 'karat', 'period'));
    }
}
