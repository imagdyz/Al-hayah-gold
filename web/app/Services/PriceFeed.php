<?php

namespace App\Services;

use App\Contracts\PriceSource;
use App\Models\GoldPrice;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/** Saves prices from automatic sources and halts online orders when they look wrong. */
class PriceFeed
{
    public function __construct(private GoldPricing $pricing) {}

    /**
     * Returns what happened, for the console. The jump check compares with the
     * last price from the same source, so switching sources (or replacing the
     * demo data) is not mistaken for a bad reading. With $fresh, prices from
     * other sources are dropped once the new one is saved.
     */
    public function refresh(PriceSource $source, bool $fresh = false): string
    {
        $base = $source->fetchBase24();
        if ($base === null) {
            return "No new price from the {$source->name()} source.";
        }

        $last = (float) GoldPrice::where('source', $source->name())->latest('recorded_at')->latest('id')->value('base_24');
        $limit = (float) config('gold.max_jump_percent');
        if ($last > 0 && abs($base - $last) / $last * 100 > $limit) {
            $this->halt(sprintf('سعر %s جه %s ج.م وآخر سعر كان %s، والفرق أكبر من %s%%.', $source->name(), number_format($base, 2), number_format($last, 2), $limit));
            Log::warning('Gold price jump rejected', ['source' => $source->name(), 'new' => $base, 'last' => $last]);

            return "Rejected {$base}: more than {$limit}% away from {$last}. Online orders halted.";
        }

        Setting::put('price_checked_at', now()->toIso8601String());
        $resumed = $this->resumeAfterStale();

        if (! $fresh && $last > 0 && abs($base - $last) < 0.01) {
            return "Price unchanged: {$base} EGP/g".$resumed;
        }

        GoldPrice::create(['base_24' => $base, 'source' => $source->name(), 'recorded_at' => now()]);
        $dropped = $fresh ? GoldPrice::where('source', '!=', $source->name())->delete() : 0;
        $this->pricing->forget();

        return "Saved 24k base price: {$base} EGP/g".($dropped ? " (dropped {$dropped} older prices from other sources)" : '').$resumed;
    }

    /** Halts online orders when an automatic source has gone quiet. */
    public function checkStale(): string
    {
        if (config('gold.source') === 'manual') {
            return 'Manual prices: nothing to check.';
        }

        // A check that found the same price still counts as fresh.
        $checked = Setting::get('price_checked_at');
        $at = collect([$this->pricing->updatedAt(), $checked ? Carbon::parse($checked) : null])->filter()->max();
        $minutes = (int) config('gold.stale_minutes');
        if ($at && $at->gt(now()->subMinutes($minutes))) {
            return 'Prices are fresh.';
        }

        if (! $this->pricing->halted()) {
            $this->halt("مفيش سعر جديد من {$minutes} دقيقة.", 'stale');
        }

        return "No price for {$minutes} minutes. Online orders halted.";
    }

    /** $kind "stale" lifts itself once prices come back; anything else waits for an admin. */
    private function halt(string $reason, string $kind = 'check'): void
    {
        Setting::put('trading_halted', true);
        Setting::put('halt_reason', $reason);
        Setting::put('halt_kind', $kind);
    }

    private function resumeAfterStale(): string
    {
        if (! $this->pricing->halted() || Setting::get('halt_kind') !== 'stale') {
            return '';
        }
        Setting::put('trading_halted', false);
        Setting::put('halt_reason', '');
        Setting::put('halt_kind', '');

        return ' Online orders resumed.';
    }
}
