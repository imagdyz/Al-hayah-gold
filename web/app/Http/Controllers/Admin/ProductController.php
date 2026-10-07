<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Category;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Services\StockSync;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $cat = Category::tryFrom((string) $request->query('category'));
        $products = Product::withCount(['pieces as in_stock' => fn ($q) => $q->where('status', 'in_stock')])
            ->when($cat, fn ($q) => $q->where('category', $cat->value))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")))
            ->orderBy('sort')->orderBy('id')->paginate(30)->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'branches' => Branch::active()->get(),
            'cat' => $cat,
        ]);
    }

    public function create()
    {
        return view('admin.products.form', ['product' => new Product(['karat' => 21, 'is_published' => true]), 'branches' => Branch::active()->get()]);
    }

    public function store(Request $request, StockSync $stock)
    {
        $product = Product::create($this->validated($request));
        $stock->product($product);

        return redirect()->route('admin.products.edit', $product)->with('status', 'المنتج اتضاف. سجّل قطعه بالكود عشان يبقى ليه مخزون.');
    }

    public function edit(Product $product)
    {
        $product->load(['branches', 'pieces' => fn ($q) => $q->with('branch')->orderBy('status')->latest('id')]);

        return view('admin.products.form', ['product' => $product, 'branches' => Branch::active()->get()]);
    }

    public function update(Request $request, Product $product, StockSync $stock)
    {
        $product->update($this->validated($request, $product));
        $product->pieces()->where('status', 'in_stock')->update([
            'name' => $product->name, 'category' => $product->category->value, 'karat' => $product->karat,
        ]);
        $stock->product($product);

        return back()->with('status', 'المنتج اتحفظ.');
    }

    public function destroy(Product $product)
    {
        $product->update(['is_published' => false]);

        return redirect()->route('admin.products.index')->with('status', 'المنتج اتخفى من الموقع والتطبيق.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::enum(Category::class)],
            'karat' => ['required', Rule::in([18, 21, 24])],
            'weight_g' => ['required', 'numeric', 'min:0.1', 'max:5000'],
            'making_fee' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'sku' => ['nullable', 'string', 'max:40', Rule::unique('products', 'sku')->ignore($product)],
            'subtitle' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:4096'],
            'is_published' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        } else {
            unset($data['image']);
        }
        $data['is_published'] = $request->boolean('is_published');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['sort'] ??= 0;
        if (! $product) {
            $data['slug'] = Str::slug(Str::ascii($data['name'])) ?: 'p';
            $data['slug'] .= '-'.Str::lower(Str::random(5));
        }

        return $data;
    }
}
