<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\LineKind;
use App\Enums\PieceStatus;
use App\Models\Invoice;
use App\Models\Piece;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Issues and voids the shop's invoices. Totals are always recomputed here. */
class PosService
{
    public function __construct(private GoldPricing $pricing) {}

    /**
     * @param  array{customer_name?: ?string, customer_phone?: ?string, settlement: string, notes?: ?string,
     *     sales?: list<array{piece_id: int, gram_price?: ?float, making_fee?: ?float}>,
     *     purchases?: list<array{description: string, karat: int, gross_weight: float, net_weight: float, gram_price?: ?float}>}  $data
     */
    public function issue(array $data, User $cashier): Invoice
    {
        $sales = $data['sales'] ?? [];
        $purchases = $data['purchases'] ?? [];

        if (! $sales && ! $purchases) {
            throw ValidationException::withMessages(['sales' => 'الفاتورة فاضية: ضيف قطعة للبيع أو ذهب مشترى.']);
        }

        return DB::transaction(function () use ($data, $sales, $purchases, $cashier) {
            $ids = array_map(fn ($s) => (int) $s['piece_id'], $sales);
            if (count($ids) !== count(array_unique($ids))) {
                throw ValidationException::withMessages(['sales' => 'نفس القطعة متضافة مرتين.']);
            }

            $pieces = Piece::query()->whereKey($ids)->lockForUpdate()->get()->keyBy('id');
            $lines = [];

            foreach ($sales as $s) {
                $piece = $pieces->get((int) $s['piece_id']);
                if (! $piece || ! $piece->isInStock()) {
                    throw ValidationException::withMessages(['sales' => 'القطعة '.($piece?->barcode ?? '').' مش في المخزون.']);
                }
                $gram = (float) ($s['gram_price'] ?? $this->pricing->sell((string) $piece->karat));
                $making = (float) ($s['making_fee'] ?? $piece->making_fee);
                $lines[] = [
                    'kind' => LineKind::Sale,
                    'piece_id' => $piece->id,
                    'description' => $piece->name,
                    'karat' => $piece->karat,
                    'gross_weight' => $piece->weight_g,
                    'net_weight' => $piece->weight_g,
                    'gram_price' => $gram,
                    'making_fee' => $making,
                    'cost' => $piece->cost,
                    'total' => self::saleTotal((float) $piece->weight_g, $gram, $making),
                ];
            }

            foreach ($purchases as $p) {
                $net = (float) $p['net_weight'];
                $gram = (float) ($p['gram_price'] ?? $this->pricing->buy((string) $p['karat']));
                $lines[] = [
                    'kind' => LineKind::Purchase,
                    'piece_id' => null,
                    'description' => trim((string) $p['description']),
                    'karat' => (int) $p['karat'],
                    'gross_weight' => (float) $p['gross_weight'],
                    'net_weight' => $net,
                    'gram_price' => $gram,
                    'making_fee' => 0,
                    'cost' => null,
                    'total' => self::purchaseTotal($net, $gram),
                ];
            }

            $salesTotal = array_sum(array_column(array_filter($lines, fn ($l) => $l['kind'] === LineKind::Sale), 'total'));
            $purchasesTotal = array_sum(array_column(array_filter($lines, fn ($l) => $l['kind'] === LineKind::Purchase), 'total'));

            $invoice = Invoice::create([
                'number' => $this->nextNumber(),
                'user_id' => $cashier->id,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'settlement' => $data['settlement'],
                'base_24' => $this->pricing->base(),
                'sales_total' => $salesTotal,
                'purchases_total' => $purchasesTotal,
                'net' => $salesTotal - $purchasesTotal,
                'status' => InvoiceStatus::Completed,
                'issued_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);
            $invoice->lines()->createMany($lines);

            if ($ids) {
                Piece::query()->whereKey($ids)->update(['status' => PieceStatus::Sold->value]);
            }

            return $invoice;
        });
    }

    /** Cancels an invoice and puts its pieces back in stock. The record stays. */
    public function void(Invoice $invoice, string $reason): void
    {
        if ($invoice->isVoided()) {
            return;
        }

        DB::transaction(function () use ($invoice, $reason) {
            $ids = $invoice->sales()->whereNotNull('piece_id')->pluck('piece_id');
            Piece::query()->whereKey($ids)->update(['status' => PieceStatus::InStock->value]);
            $invoice->update(['status' => InvoiceStatus::Voided, 'void_reason' => $reason, 'voided_at' => now()]);
        });
    }

    public static function saleTotal(float $weight, float $gramPrice, float $making): float
    {
        return round($weight * $gramPrice) + round($making);
    }

    public static function purchaseTotal(float $netWeight, float $gramPrice): float
    {
        return round($netWeight * $gramPrice);
    }

    private function nextNumber(): int
    {
        $last = Invoice::query()->lockForUpdate()->max('number');

        return max((int) $last + 1, Setting::int('invoice_start'));
    }
}
