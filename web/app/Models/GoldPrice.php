<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoldPrice extends Model
{
    public $timestamps = false;

    protected $fillable = ['base_24', 'source', 'recorded_at'];

    protected function casts(): array
    {
        return ['base_24' => 'float', 'recorded_at' => 'datetime'];
    }
}
