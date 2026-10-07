<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\LineKind;
use App\Enums\Settlement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'number', 'user_id', 'customer_name', 'customer_phone', 'settlement', 'base_24',
        'sales_total', 'purchases_total', 'net', 'status', 'void_reason', 'voided_at', 'issued_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'int',
            'settlement' => Settlement::class,
            'status' => InvoiceStatus::class,
            'base_24' => 'decimal:2',
            'sales_total' => 'decimal:2',
            'purchases_total' => 'decimal:2',
            'net' => 'decimal:2',
            'voided_at' => 'datetime',
            'issued_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function sales(): HasMany
    {
        return $this->lines()->where('kind', LineKind::Sale->value);
    }

    public function purchases(): HasMany
    {
        return $this->lines()->where('kind', LineKind::Purchase->value);
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', InvoiceStatus::Completed->value);
    }

    public function isVoided(): bool
    {
        return $this->status === InvoiceStatus::Voided;
    }

    /** The number as printed on the paper book: at least 4 digits. */
    public function label(): string
    {
        return str_pad((string) $this->number, 4, '0', STR_PAD_LEFT);
    }

    /** Who pays the difference: positive means the customer pays the shop. */
    public function netLabel(): string
    {
        return match (true) {
            $this->net > 0 => 'العميل يدفع',
            $this->net < 0 => 'المحل يدفع',
            default => 'لا يوجد فرق',
        };
    }
}
