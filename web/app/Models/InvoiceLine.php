<?php

namespace App\Models;

use App\Enums\LineKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'invoice_id', 'kind', 'piece_id', 'description', 'karat', 'gross_weight', 'net_weight',
        'gram_price', 'making_fee', 'cost', 'total',
    ];

    protected function casts(): array
    {
        return [
            'kind' => LineKind::class,
            'karat' => 'int',
            'gross_weight' => 'decimal:3',
            'net_weight' => 'decimal:3',
            'gram_price' => 'decimal:2',
            'making_fee' => 'decimal:2',
            'cost' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function piece(): BelongsTo
    {
        return $this->belongsTo(Piece::class);
    }
}
