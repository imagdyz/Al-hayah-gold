<?php

namespace App\Http\Controllers\Site;

use App\Enums\Category;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Services\GoldPricing;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request, GoldPricing $pricing)
    {
        $filter = $request->query('c');
        $jewelry = Product::published()->jewelry()->with('branches')
            ->when($filter && array_key_exists($filter, Category::filters()), fn ($q) => $q->whereIn('category', array_map(fn ($c) => $c->value, Category::forFilter($filter))))
            ->orderByDesc('is_featured')->orderBy('sort')->take(8)->get();

        $bullion = Product::published()->bullion()->with('branches')->orderBy('category')->orderBy('weight_g')->get();

        $bars = $bullion->where('category', Category::Bar)->sortByDesc('weight_g')
            ->map(fn (Product $p) => ['name' => 'سبيكة '.$p->weightLabel(), 'grams' => (float) $p->weight_g, 'price' => $p->price()])
            ->values();

        $sparks = collect(GoldPricing::KARATS)->mapWithKeys(fn ($k) => [
            $k => array_column($pricing->history($k, '24h'), 'v'),
        ]);

        return view('site.home', [
            'jewelry' => $jewelry,
            'bullion' => $bullion,
            'bars' => $bars,
            'sparks' => $sparks,
            'filter' => $filter,
            'branches' => Branch::active()->get(),
        ]);
    }
}
