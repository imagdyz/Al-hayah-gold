<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

class Branch extends Model
{
    protected $fillable = ['name', 'slug', 'area', 'address', 'phone', 'map_url', 'opens_at', 'closes_at', 'is_active', 'sort'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('quantity');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort')->orderBy('id');
    }

    public function fullName(): string
    {
        return $this->area ? "{$this->name} — {$this->area}" : $this->name;
    }

    public function isOpen(?Carbon $at = null): bool
    {
        $at ??= now();
        $time = $at->format('H:i:s');

        return $time >= $this->opens_at && $time < $this->closes_at;
    }

    public function statusLabel(): string
    {
        return $this->isOpen() ? 'مفتوح الآن' : 'يفتح '.self::formatTime($this->opens_at);
    }

    public function hoursLabel(): string
    {
        return 'من '.self::formatTime($this->opens_at).' لـ '.self::formatTime($this->closes_at);
    }

    public static function formatTime(string $time): string
    {
        $t = Carbon::createFromFormat('H:i:s', strlen($time) === 5 ? $time.':00' : $time);
        $suffix = $t->hour < 12 ? 'ص' : ($t->hour < 17 ? 'ظ' : 'م');
        $hour = $t->hour % 12 ?: 12;

        return $t->minute ? sprintf('%d:%02d %s', $hour, $t->minute, $suffix) : "{$hour} {$suffix}";
    }
}
