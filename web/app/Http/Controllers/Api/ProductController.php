<?php

namespace App\Http\Controllers\Api;

use App\Enums\Category;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'filter' => ['nullable', 'string'],
            'karat' => ['nullable', 'in:18,21,24'],
            'branch' => ['nullable', 'integer'],
        ]);
        $filter = array_key_exists($data['filter'] ?? '', Category::filters()) ? $data['filter'] : null;

        $products = Product::published()->with('branches')
            ->when($filter, fn ($q) => $q->whereIn('category', array_map(fn ($c) => $c->value, Category::forFilter($filter))))
            ->when($data['karat'] ?? null, fn ($q, $k) => $q->where('karat', $k))
            ->when($data['branch'] ?? null, fn ($q, $b) => $q->whereHas('branches', fn ($w) => $w->where('branches.id', $b)->where('quantity', '>', 0)))
            ->orderBy('sort')->get();

        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        abort_unless($product->is_published, 404);

        return new ProductResource($product->load('branches'));
    }
}
