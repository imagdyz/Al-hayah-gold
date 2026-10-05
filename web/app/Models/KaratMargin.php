<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KaratMargin extends Model
{
    protected $fillable = ['karat', 'sell_margin', 'buy_margin'];

    protected function casts(): array
    {
        return ['sell_margin' => 'float', 'buy_margin' => 'float'];
    }
}
