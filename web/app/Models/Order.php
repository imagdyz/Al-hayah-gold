<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'code', 'type', 'status', 'user_id', 'customer_name', 'phone', 'branch_id', 'product_id', 'quantity',
        'karat', 'weight_g', 'unit_price', 'total', 'deposit', 'pay_method', 'locked_until', 'slot_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => OrderType::class,
            'status' => OrderStatus::class,
            'weight_g' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'deposit' => 'decimal:2',
            'locked_until' => 'datetime',
            'slot_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', array_map(fn ($s) => $s->value, OrderStatus::open()));
    }

    public static function newCode(): string
    {
        do {
            $code = 'AH-'.random_int(100000, 999999);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function title(): string
    {
        return match ($this->type) {
            OrderType::Sell => 'بيع ذهب عيار '.$this->karat,
            default => ($this->product?->name ?? 'منتج').($this->quantity > 1 ? ' × '.$this->quantity : ''),
        };
    }

    public function payLabel(): string
    {
        if ($this->type === OrderType::Sell) {
            return 'تستلم فلوسك في الفرع';
        }

        return $this->pay_method === 'deposit' ? 'عربون والباقي في الفرع' : 'الدفع في الفرع';
    }

    public function imageSrc(): string
    {
        return Product::imageUrl($this->product?->image ?? 'images/products/bracelet-braided.webp');
    }
}
