<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PieceStatus;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * The site's stock per branch comes from the tagged pieces: pieces in stock
 * minus what open online orders already hold. Nobody types it in.
 */
class StockSync
{
    public function product(Product|int $product): void
    {
        $id = $product instanceof Product ? $product->id : $product;

        $pieces = DB::table('pieces')->where('product_id', $id)->where('status', PieceStatus::InStock->value)
            ->groupBy('branch_id')->pluck(DB::raw('count(*)'), 'branch_id');
        $held = DB::table('orders')->where('product_id', $id)
            ->whereIn('type', [OrderType::Bullion->value, OrderType::Reservation->value])
            ->whereIn('status', array_map(fn ($s) => $s->value, OrderStatus::open()))
            ->groupBy('branch_id')->pluck(DB::raw('sum(quantity)'), 'branch_id');

        $rows = Branch::query()->pluck('id')->mapWithKeys(fn ($branch) => [
            $branch => ['quantity' => max(0, (int) ($pieces[$branch] ?? 0) - (int) ($held[$branch] ?? 0))],
        ]);

        Product::query()->find($id)?->branches()->sync($rows->all());
    }

    /** @param  iterable<int|null>  $ids */
    public function products(iterable $ids): void
    {
        foreach (collect($ids)->filter()->unique() as $id) {
            $this->product((int) $id);
        }
    }
}
