<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Category;
use App\Enums\PieceStatus;
use App\Http\Controllers\Controller;
use App\Models\Piece;
use App\Services\PosReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PieceController extends Controller
{
    public function index(Request $request, PosReport $report)
    {
        $status = $request->query('status', PieceStatus::InStock->value) === 'all'
            ? null : (PieceStatus::tryFrom((string) $request->query('status')) ?? PieceStatus::InStock);
        $category = Category::tryFrom((string) $request->query('category'));
        $karat = in_array((int) $request->query('karat'), PosController::KARATS, true) ? (int) $request->query('karat') : null;

        $pieces = Piece::query()
            ->when($status, fn ($q) => $q->where('status', $status->value))
            ->when($category, fn ($q) => $q->where('category', $category->value))
            ->when($karat, fn ($q) => $q->where('karat', $karat))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('barcode', $s)->orWhere('name', 'like', "%{$s}%")))
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

    public function create()
    {
        return view('admin.pieces.form', ['piece' => new Piece(['karat' => 21, 'category' => Category::Ring])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['barcode'] = ($data['barcode'] ?? null) ?: Piece::nextBarcode();
        $piece = Piece::create($data + ['status' => PieceStatus::InStock]);

        $next = $request->input('after') === 'another' ? route('admin.pieces.create') : route('admin.pieces.index');

        return redirect($next)->with('status', "القطعة {$piece->barcode} اتضافت للمخزون.")->with('label', $piece->id);
    }

    public function edit(Piece $piece)
    {
        return view('admin.pieces.form', ['piece' => $piece->loadCount('lines')]);
    }

    public function update(Request $request, Piece $piece)
    {
        abort_unless($piece->isInStock(), 403, 'القطعة اتباعت، ومينفعش تتعدّل.');
        $data = $this->validated($request, $piece);
        $data['barcode'] = ($data['barcode'] ?? null) ?: $piece->barcode;
        $piece->update($data);

        return redirect()->route('admin.pieces.index')->with('status', "القطعة {$piece->barcode} اتعدّلت.");
    }

    public function destroy(Piece $piece)
    {
        abort_if($piece->lines()->exists(), 403, 'القطعة عليها فواتير، ومينفعش تتمسح.');
        $piece->delete();

        return redirect()->route('admin.pieces.index')->with('status', "القطعة {$piece->barcode} اتمسحت.");
    }

    /** Barcode labels for one or more pieces, ready for the label printer. */
    public function labels(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids')))->map(fn ($id) => (int) $id)->filter()->take(200);
        $pieces = Piece::query()->whereKey($ids)->orderBy('id')->get();
        abort_if($pieces->isEmpty(), 404);

        return view('admin.pieces.labels', ['pieces' => $pieces, 'auto' => ! $request->boolean('preview')]);
    }

    private function validated(Request $request, ?Piece $piece = null): array
    {
        return $request->validate([
            'barcode' => ['nullable', 'string', 'max:40', 'regex:/^[\x21-\x7E]+$/', Rule::unique('pieces', 'barcode')->ignore($piece)],
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::enum(Category::class)],
            'karat' => ['required', Rule::in(PosController::KARATS)],
            'weight_g' => ['required', 'numeric', 'min:0.001', 'max:100000'],
            'making_fee' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'barcode.regex' => 'الباركود بالحروف الإنجليزي والأرقام بس، من غير مسافات.',
            'barcode.unique' => 'الباركود ده متسجل على قطعة تانية.',
        ]);
    }
}
