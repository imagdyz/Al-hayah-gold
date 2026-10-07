<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\PosService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $status = InvoiceStatus::tryFrom((string) $request->query('status'));
        $from = $this->date($request->query('from'));
        $to = $this->date($request->query('to'));

        $invoices = Invoice::query()
            ->when($status, fn ($q) => $q->where('status', $status->value))
            ->when($from, fn ($q) => $q->where('issued_at', '>=', $from->startOfDay()))
            ->when($to, fn ($q) => $q->where('issued_at', '<=', $to->endOfDay()))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->when(ctype_digit($s), fn ($n) => $n->orWhere('number', (int) $s))
                ->orWhere('customer_name', 'like', "%{$s}%")
                ->orWhere('customer_phone', 'like', "%{$s}%")))
            ->withCount(['sales', 'purchases'])
            ->latest('issued_at')->latest('id')
            ->paginate(30)->withQueryString();

        return view('admin.invoices.index', compact('invoices', 'status'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['lines.piece', 'user']);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function print(Request $request, Invoice $invoice)
    {
        $invoice->load(['lines', 'user']);
        $format = $request->query('format') === '80mm' ? '80mm' : 'a5';

        return view("admin.invoices.print-{$format}", [
            'invoice' => $invoice,
            'shop' => $this->shop(),
            'auto' => ! $request->boolean('preview'),
        ]);
    }

    public function void(Request $request, Invoice $invoice, PosService $pos)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']]);
        $pos->void($invoice, $data['reason']);

        return back()->with('status', "الفاتورة رقم {$invoice->label()} اتلغت والقطع رجعت المخزون.");
    }

    /** @return array<string, string> */
    private function shop(): array
    {
        return collect(['shop_name', 'shop_tagline', 'shop_address', 'shop_phone'])
            ->mapWithKeys(fn ($k) => [substr($k, 5) => (string) Setting::get($k)])->all();
    }

    private function date(mixed $value): ?Carbon
    {
        try {
            return is_string($value) && $value !== '' ? Carbon::parse($value) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
