<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\LineKind;
use App\Enums\PieceStatus;
use App\Models\Piece;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/** Figures for the reports page and the dashboard. Voided invoices never count. */
class PosReport
{
    public function __construct(private GoldPricing $pricing) {}

    public function summary(CarbonInterface $from, CarbonInterface $to): array
    {
        $lines = fn (LineKind $kind) => DB::table('invoice_lines')
            ->join('invoices', 'invoices.id', '=', 'invoice_lines.invoice_id')
            ->where('invoices.status', InvoiceStatus::Completed->value)
            ->whereBetween('invoices.issued_at', [$from, $to])
            ->where('invoice_lines.kind', $kind->value);

        $sales = $lines(LineKind::Sale)->selectRaw('count(*) as pieces, coalesce(sum(total),0) as total, coalesce(sum(making_fee),0) as making')->first();
        $costed = $lines(LineKind::Sale)->whereNotNull('cost')->selectRaw('count(*) as n, coalesce(sum(total),0) as total, coalesce(sum(cost),0) as cost')->first();

        $invoices = DB::table('invoices')->where('status', InvoiceStatus::Completed->value)->whereBetween('issued_at', [$from, $to]);

        return [
            'invoices' => (clone $invoices)->count(),
            'sale_invoices' => (clone $invoices)->where('sales_total', '>', 0)->count(),
            'sales' => (float) $sales->total,
            'pieces' => (int) $sales->pieces,
            'making' => (float) $sales->making,
            'purchases' => (float) (clone $invoices)->sum('purchases_total'),
            'net' => (float) (clone $invoices)->sum('net'),
            'profit' => (float) $costed->total - (float) $costed->cost,
            'uncosted' => (int) $sales->pieces - (int) $costed->n,
            'sold_by_karat' => $lines(LineKind::Sale)->groupBy('invoice_lines.karat')->orderByDesc('invoice_lines.karat')
                ->selectRaw('invoice_lines.karat as karat, count(*) as n, sum(net_weight) as weight, sum(total) as total')->get(),
            'bought_by_karat' => $lines(LineKind::Purchase)->groupBy('invoice_lines.karat')->orderByDesc('invoice_lines.karat')
                ->selectRaw('invoice_lines.karat as karat, sum(gross_weight) as gross, sum(net_weight) as weight, sum(total) as total')->get(),
            'by_settlement' => (clone $invoices)->groupBy('settlement')
                ->selectRaw('settlement, count(*) as n, sum(net) as net')->get()->keyBy('settlement'),
            'daily' => (clone $invoices)->groupByRaw('date(issued_at)')->orderByRaw('date(issued_at) desc')
                ->selectRaw('date(issued_at) as day, count(*) as n, sum(sales_total) as sales, sum(purchases_total) as purchases, sum(net) as net')->get(),
        ];
    }

    /** Pieces in stock per karat, with their value at today's selling price. */
    public function stock(): array
    {
        $rows = Piece::query()->where('status', PieceStatus::InStock->value)->groupBy('karat')->orderByDesc('karat')
            ->selectRaw('karat, count(*) as n, sum(weight_g) as weight, sum(making_fee) as making')->get()
            ->map(fn ($r) => [
                'karat' => (int) $r->karat,
                'n' => (int) $r->n,
                'weight' => (float) $r->weight,
                'value' => round((float) $r->weight * $this->pricing->sell((string) $r->karat)) + (float) $r->making,
            ]);

        return [
            'rows' => $rows,
            'n' => $rows->sum('n'),
            'weight' => $rows->sum('weight'),
            'value' => $rows->sum('value'),
        ];
    }
}
