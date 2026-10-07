<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Settlement;
use App\Http\Controllers\Controller;
use App\Models\Piece;
use App\Services\GoldPricing;
use App\Services\PosService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PosController extends Controller
{
    public const KARATS = [24, 21, 18];

    public function create(GoldPricing $pricing)
    {
        $prices = collect(self::KARATS)->mapWithKeys(fn (int $k) => [$k => [
            'sell' => $pricing->sell((string) $k),
            'buy' => $pricing->buy((string) $k),
        ]]);

        return view('admin.pos.create', [
            'prices' => $prices,
            'updatedAt' => $pricing->updatedAt(),
            'halted' => $pricing->halted(),
        ]);
    }

    /** Looks a piece up by the code the scanner typed. */
    public function piece(Request $request, GoldPricing $pricing)
    {
        $code = trim((string) $request->query('barcode'));
        $piece = Piece::findByCode($code);

        if (! $piece) {
            return response()->json(['message' => "مفيش قطعة بالباركود {$code}."], 404);
        }
        if (! $piece->isInStock()) {
            return response()->json(['message' => "القطعة {$piece->barcode} اتباعت قبل كده."], 422);
        }

        return response()->json([
            'id' => $piece->id,
            'barcode' => $piece->barcode,
            'name' => $piece->name,
            'karat' => $piece->karat,
            'weight' => (float) $piece->weight_g,
            'making_fee' => (float) $piece->making_fee,
            'gram_price' => $pricing->sell((string) $piece->karat),
        ]);
    }

    public function store(Request $request, PosService $pos)
    {
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'settlement' => ['required', Rule::enum(Settlement::class)],
            'notes' => ['nullable', 'string', 'max:500'],
            'sales' => ['array', 'max:30'],
            'sales.*.piece_id' => ['required', 'integer', 'exists:pieces,id'],
            'sales.*.gram_price' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'sales.*.making_fee' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'purchases' => ['array', 'max:30'],
            'purchases.*.description' => ['required', 'string', 'max:120'],
            'purchases.*.karat' => ['required', Rule::in(self::KARATS)],
            'purchases.*.gross_weight' => ['required', 'numeric', 'min:0.001', 'max:100000'],
            'purchases.*.net_weight' => ['required', 'numeric', 'min:0.001', 'lte:purchases.*.gross_weight'],
            'purchases.*.gram_price' => ['required', 'numeric', 'min:1', 'max:1000000'],
        ], [
            'purchases.*.net_weight.lte' => 'وزن التحييف لازم يبقى أقل من الوزن الإجمالي أو يساويه.',
        ]);

        $invoice = $pos->issue($data, $request->user());
        $url = route('admin.invoices.show', $invoice);

        return $request->expectsJson()
            ? response()->json(['redirect' => $url, 'number' => $invoice->number])
            : redirect($url)->with('status', "الفاتورة رقم {$invoice->label()} اتسجلت.");
    }
}
