<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'trading_halted' => '0',
        'price_lock_minutes' => '30',
        'deposit_percent' => '10',
        'reservation_hours' => '48',
    ];

    public static function get(string $key): ?string
    {
        $all = Cache::rememberForever('settings', fn () => static::query()->pluck('value', 'key')->all());

        return $all[$key] ?? self::DEFAULTS[$key] ?? null;
    }

    public static function int(string $key): int
    {
        return (int) static::get($key);
    }

    public static function bool(string $key): bool
    {
        return (bool) (int) static::get($key);
    }

    public static function put(string $key, string|int|bool $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => (string) (is_bool($value) ? (int) $value : $value)]);
        Cache::forget('settings');
    }
}
