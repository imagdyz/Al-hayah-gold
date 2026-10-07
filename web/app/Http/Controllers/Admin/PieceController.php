<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Category;
use App\Enums\PieceStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Piece;
use App\Models\Product;
use App\Services\PosReport;
use App\Services\StockSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PieceController extends Controller
{
    public function index(Request $request, PosReport $report)
    {
        $status = $request->query('status', PieceStatus::InStock->value) === 'all'
            ? null : (PieceStatus::tryFrom((string) $request->query('status')) ?? PieceStatus::InStock);
        $category = Category::tryFrom((string) $request->query('category'));
        $karat = in_array((int) $request->query('karat'), PosController::KARATS, true) ? (int) $request->query('karat') : null;
        $q = trim((string) $request->query('q'));
        $exact = $q !== '' ? Piece::findByCode($q) : null;

        $pieces = Piece::query()->with('product')
            ->when($status, fn ($w) => $w->where('status', $status->value))
            ->when($category, fn ($w) => $w->where('category', $category->value))
            ->when($karat, fn ($w) => $w->where('karat', $karat))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('barcode', $exact?->barcode ?? $q)->orWhere('name', 'like', "%{$q}%")))
            ->latest('id')
            ->paginate(50)->withQueryString();

        return view('admin.pieces.index', [
            'pieces' => $pieces,
            'status' => $status,
            'category' => $category,
            'karat' => $karat,
            'stock' => $report->stock(),
        ]);
    }

    public function create(Request $request)
    {
        $product = Product::find($request->query('product'));

        return view('admin.pieces.form', $this->formData(new Piece([
            'product_id' => $product?->id,
            'weight_g' => $product?->weight_g,
            'making_fee' => $product?->making_fee,
        ])));
    }

    public function store(Request $request, StockSync $stock)
    {
        $data = $this->validated($request);
        $piece = DB::transaction(function () use ($request, $data) {
            $product = $request->input('product_id') === 'new' ? $this->newProduct($request, $data) : Product::findOrFail($data['product_id']);

            return Piece::create($this->fromProduct($product, $data) + ['status' => PieceStatus::InStock]);
        });
        $stock->product($piece->product_id);

        $next = $request->input('after') === 'another'
            ? route('admin.pieces.create', ['product' => $piece->product_id])
            : route('admin.products.edit', $piece->product);

        return redirect($next)->with('status', "القطعة {$piece->barcode} اتسجلت على «{$piece->product->name}».");
    }

    public function edit(Piece $piece)
    {
        return view('admin.pieces.form', $this->formData($piece->loadCount('lines')));
    }

    public function update(Request $request, Piece $piece, StockSync $stock)
    {
        abort_unless($piece->isInStock(), 403, 'القطعة اتباعت، ومينفعش تتعدّل.');
        $data = $this->validated($request, $piece);
        $before = $piece->product_id;
        $product = $request->input('product_id') === 'new' ? $this->newProduct($request, $data) : Product::findOrFail($data['product_id']);
        $piece->update($this->fromProduct($product, $data));
        $stock->products([$before, $piece->product_id]);

        return redirect()->route('admin.products.edit', $piece->product)->with('status', "القطعة {$piece->barcode} اتعدّلت.");
    }

    public function destroy(Piece $piece, StockSync $stock)
    {
        abort_if($piece->lines()->exists(), 403, 'القطعة عليها فواتير، ومينفعش تتمسح.');
        $piece->delete();
        $stock->product($piece->product_id);

        return redirect()->route('admin.pieces.index')->with('status', "القطعة {$piece->barcode} اتمسحت.");
    }

    /** Replacement labels for pieces whose tag was lost or damaged. */
    public function labels(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids')))->map(fn ($id) => (int) $id)->filter()->take(200);
        $pieces = Piece::query()->whereKey($ids)->orderBy('id')->get();
        abort_if($pieces->isEmpty(), 404);

        return view('admin.pieces.labels', ['pieces' => $pieces, 'auto' => ! $request->boolean('preview')]);
    }

    private function formData(Piece $piece): array
    {
        $products = Product::query()->orderBy('category')->orderBy('name')->get(['id', 'name', 'category', 'karat', 'weight_g', 'making_fee', 'sku']);

        return [
            'piece' => $piece,
            'products' => $products,
            'branches' => Branch::active()->get(),
        ];
    }

    private function validated(Request $request, ?Piece $piece = null): array
    {
        $new = $request->input('product_id') === 'new';

        return $request->validate([
            'barcode' => ['required', 'string', 'max:60', 'regex:/^[\x21-\x7E]+$/', Rule::unique('pieces', 'barcode')->ignore($piece)],
            'product_id' => $new ? ['required'] : ['required', 'integer', 'exists:products,id'],
            'new_name' => $new ? ['required', 'string', 'max:120'] : ['nullable'],
            'new_category' => $new ? ['required', Rule::enum(Category::class)] : ['nullable'],
            'new_karat' => $new ? ['required', Rule::in(PosController::KARATS)] : ['nullable'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'weight_g' => ['required', 'numeric', 'min:0.001', 'max:100000'],
            'making_fee' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'barcode.required' => 'امسح كود القطعة من التيكت. مفيش قطعة تتسجل من غير كودها.',
            'barcode.regex' => 'الكود بالحروف الإنجليزي والأرقام بس، من غير مسافات.',
            'barcode.unique' => 'الكود ده متسجل على قطعة تانية.',
            'product_id.required' => 'اختار المنتج اللي القطعة دي تبعه، أو "منتج جديد".',
            'new_name.required' => 'اكتب اسم المنتج الجديد.',
        ]);
    }

    /** A product made from the piece form; it stays off the site until it gets a photo and is published. */
    private function newProduct(Request $request, array $data): Product
    {
        return Product::create([
            'name' => $data['new_name'],
            'slug' => (Str::slug(Str::ascii($data['new_name'])) ?: 'p').'-'.Str::lower(Str::random(5)),
            'category' => $data['new_category'],
            'karat' => (int) $data['new_karat'],
            'weight_g' => $data['weight_g'],
            'making_fee' => $data['making_fee'],
            'is_published' => false,
            'is_featured' => false,
            'sort' => 0,
        ]);
    }

    private function fromProduct(Product $product, array $data): array
    {
        return [
            'barcode' => trim($data['barcode']),
            'product_id' => $product->id,
            'branch_id' => $data['branch_id'] ?? Branch::active()->value('id'),
            'name' => $product->name,
            'category' => $product->category,
            'karat' => $product->karat,
            'weight_g' => $data['weight_g'],
            'making_fee' => $data['making_fee'],
            'cost' => $data['cost'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }
}
