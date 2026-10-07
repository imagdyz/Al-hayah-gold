<?php

namespace App\Models;

use App\Enums\Category;
use App\Enums\PieceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Piece extends Model
{
    protected $fillable = ['barcode', 'product_id', 'branch_id', 'name', 'category', 'karat', 'weight_g', 'making_fee', 'cost', 'status', 'notes'];

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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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

    /**
     * The piece a scanner read. 2D tags can carry more than the printed code
     * (weight, karat...), so when the whole text matches nothing, each part
     * of it is tried as a code.
     */
    public static function findByCode(string $scanned): ?self
    {
        $scanned = trim($scanned);
        if ($scanned === '') {
            return null;
        }
        if ($piece = static::query()->where('barcode', $scanned)->first()) {
            return $piece;
        }

        $parts = array_values(array_filter(preg_split('/[^A-Za-z0-9\-]+/', $scanned) ?: [], fn ($p) => strlen($p) >= 4));

        return $parts ? static::query()->whereIn('barcode', $parts)->first() : null;
    }
}
