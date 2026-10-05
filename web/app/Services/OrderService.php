<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private GoldPricing $pricing) {}

    /** Bullion ordered online at a locked price and collected from a branch. */
    public function placeBullion(User $user, Product $product, int $quantity, Branch $branch, string $payMethod): Order
    {
        $this->ensureTrading();
        if (! $product->isBullion()) {
            throw ValidationException::withMessages(['product' => 'المنتج ده مش سبيكة ولا جنيه.']);
        }

        return $this->place($user, $product, $branch, $quantity, $payMethod, OrderType::Bullion, null);
    }

    /** A jewelry piece held at a branch for the customer to see before paying. */
    public function placeReservation(User $user, Product $product, Branch $branch, Carbon $slot, string $payMethod): Order
    {
        $this->ensureTrading();

        return $this->place($user, $product, $branch, 1, $payMethod, OrderType::Reservation, $slot);
    }

    /** An appointment to sell or trade in old gold at a branch. */
    public function bookSell(User $user, Branch $branch, int $karat, float $grams, Carbon $slot, ?string $notes = null): Order
    {
        $estimate = $this->pricing->buyBack($karat, $grams);

        return Order::create([
            'code' => Order::newCode(),
            'type' => OrderType::Sell,
            'status' => OrderStatus::New,
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'phone' => $user->phone,
            'branch_id' => $branch->id,
            'karat' => $karat,
            'weight_g' => $grams,
            'unit_price' => $this->pricing->buy((string) $karat),
            'total' => $estimate,
            'pay_method' => 'branch',
            'slot_at' => $slot,
            'notes' => $notes,
        ]);
    }

    public function setStatus(Order $order, OrderStatus $status): void
    {
        DB::transaction(function () use ($order, $status) {
            $wasHolding = $order->status->isOpen() && $order->type !== OrderType::Sell;
            if ($status === OrderStatus::Cancelled && $wasHolding && $order->product_id) {
                $this->adjustStock($order->branch_id, $order->product_id, $order->quantity);
            }
            $order->update(['status' => $status]);
        });
    }

    private function place(User $user, Product $product, Branch $branch, int $quantity, string $payMethod, OrderType $type, ?Carbon $slot): Order
    {
        return DB::transaction(function () use ($user, $product, $branch, $quantity, $payMethod, $type, $slot) {
            $stock = (int) DB::table('branch_product')
                ->where('branch_id', $branch->id)->where('product_id', $product->id)
                ->lockForUpdate()->value('quantity');

            if ($stock < $quantity) {
                throw ValidationException::withMessages([
                    'branch_id' => $stock > 0
                        ? "متاح في الفرع ده {$stock} بس. قلّل الكمية أو اختار فرع تاني."
                        : 'القطعة دي مش موجودة في الفرع ده دلوقتي. اختار فرع تاني.',
                ]);
            }

            $this->adjustStock($branch->id, $product->id, -$quantity);

            $unit = $this->pricing->productPrice($product);
            $total = $unit * $quantity;
            $deposit = $payMethod === 'deposit' ? (int) round($total * Setting::int('deposit_percent') / 100) : 0;
            $lockMinutes = $type === OrderType::Reservation ? Setting::int('reservation_hours') * 60 : Setting::int('price_lock_minutes');

            return Order::create([
                'code' => Order::newCode(),
                'type' => $type,
                'status' => OrderStatus::New,
                'user_id' => $user->id,
                'customer_name' => $user->name,
                'phone' => $user->phone,
                'branch_id' => $branch->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'karat' => $product->karat,
                'weight_g' => (float) $product->weight_g * $quantity,
                'unit_price' => $unit,
                'total' => $total,
                'deposit' => $deposit,
                'pay_method' => $payMethod,
                'locked_until' => now()->addMinutes($lockMinutes),
                'slot_at' => $slot,
            ]);
        });
    }

    private function adjustStock(int $branchId, int $productId, int $delta): void
    {
        $row = DB::table('branch_product')->where('branch_id', $branchId)->where('product_id', $productId);
        $current = (int) $row->value('quantity');
        $row->update(['quantity' => max(0, $current + $delta)]);
    }

    private function ensureTrading(): void
    {
        if ($this->pricing->halted()) {
            throw ValidationException::withMessages([
                'trading' => 'الطلبات أونلاين واقفة مؤقتاً بسبب تحرّك السعر. جرّب كمان شوية أو كلّم الفرع.',
            ]);
        }
    }
}
