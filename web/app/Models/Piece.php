<?php

namespace App\Models;

use App\Enums\Category;
use App\Enums\PieceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Piece extends Model
{
    protected $fillable = ['barcode', 'name', 'category', 'karat', 'weight_g', 'making_fee', 'cost', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'category' => Category::class,
            'status' => PieceStatus::class,
            'karat' => 'int',
            'weight_g' => 'decimal:3',
            'making_fee' => 'decimal:2',
            'cost' => 'decimal:2',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function scopeInStock(Builder $query): void
    {
        $query->where('status', PieceStatus::InStock->value);
    }

    public function isInStock(): bool
    {
        return $this->status === PieceStatus::InStock;
    }

    /** Numeric barcodes the shop's own labels use: 2 + the id padded to 7 digits. */
    public static function nextBarcode(): string
    {
        $next = (int) static::query()->max('id') + 1;
        do {
            $code = '2'.str_pad((string) $next++, 7, '0', STR_PAD_LEFT);
        } while (static::query()->where('barcode', $code)->exists());

        return $code;
    }
}
