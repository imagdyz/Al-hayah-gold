<?php

namespace App\Http\Controllers\Site;

use App\Enums\Category;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'c' => ['nullable', 'string'],
            'karat' => ['nullable', 'array'],
            'karat.*' => ['in:18,21,24'],
            'branch' => ['nullable', 'integer'],
            'min' => ['nullable', 'numeric', 'min:0'],
            'max' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:new,price_asc,price_desc,weight'],
            'q' => ['nullable', 'string', 'max:60'],
        ]);
        $cat = array_key_exists($filters['c'] ?? '', Category::filters()) ? $filters['c'] : null;

        $query = Product::published()->with('branches')
            ->when($cat, fn ($q) => $q->whereIn('category', array_map(fn ($c) => $c->value, Category::forFilter($cat))))
            ->when($filters['karat'] ?? null, fn ($q, $k) => $q->whereIn('karat', $k))
            ->when($filters['branch'] ?? null, fn ($q, $b) => $q->whereHas('branches', fn ($w) => $w->where('branches.id', $b)->where('quantity', '>', 0)))
            ->when($filters['min'] ?? null, fn ($q, $v) => $q->where('weight_g', '>=', $v))
            ->when($filters['max'] ?? null, fn ($q, $v) => $q->where('weight_g', '<=', $v))
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where('name', 'like', '%'.$s.'%'));

        // Price depends on today's gold price, so sort by it after loading.
        $products = $query->orderByDesc('is_featured')->orderBy('sort')->get();
        $products = match ($filters['sort'] ?? 'new') {
            'price_asc' => $products->sortBy(fn ($p) => $p->price()),
            'price_desc' => $products->sortByDesc(fn ($p) => $p->price()),
            'weight' => $products->sortByDesc('weight_g'),
            default => $products,
        };

        return view('site.shop.index', [
            'products' => $products->values(),
            'cat' => $cat,
            'filters' => $filters,
            'branches' => Branch::active()->get(),
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->is_published, 404);
        $product->load('branches');

        $related = Product::published()->with('branches')
            ->where('id', '!=', $product->id)
            ->when($product->isBullion(), fn ($q) => $q->bullion(), fn ($q) => $q->jewelry())
            ->orderByRaw('category = ? desc', [$product->category->value])
            ->orderBy('sort')->take(4)->get();

        return view('site.shop.show', [
            'product' => $product,
            'related' => $related,
            'branches' => Branch::active()->get(),
        ]);
    }
}
