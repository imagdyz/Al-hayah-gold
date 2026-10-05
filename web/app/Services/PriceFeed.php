<?php

namespace App\Services;

use App\Contracts\PriceSource;
use App\Models\GoldPrice;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/** Saves prices from automatic sources and halts online orders when they look wrong. */
class PriceFeed
{
    public function __construct(private GoldPricing $pricing) {}

    /** Returns what happened, for the console. */
    public function refresh(PriceSource $source): string
    {
        $base = $source->fetchBase24();
        if ($base === null) {
            return "No new price from the {$source->name()} source.";
        }

        $last = $this->pricing->base();
        $limit = (float) config('gold.max_jump_percent');
        if ($last > 0 && abs($base - $last) / $last * 100 > $limit) {
            $this->halt(sprintf('سعر %s جه %s ج.م وآخر سعر كان %s، والفرق أكبر من %s%%.', $source->name(), number_format($base, 2), number_format($last, 2), $limit));
            Log::warning('Gold price jump rejected', ['source' => $source->name(), 'new' => $base, 'last' => $last]);

            return "Rejected {$base}: more than {$limit}% away from {$last}. Online orders halted.";
        }

        GoldPrice::create(['base_24' => $base, 'source' => $source->name(), 'recorded_at' => now()]);
        $this->pricing->forget();

        return "Saved 24k base price: {$base} EGP/g";
    }

    /** Halts online orders when an automatic source has gone quiet. */
    public function checkStale(): string
    {
        if (config('gold.source') === 'manual') {
            return 'Manual prices: nothing to check.';
        }

        $at = $this->pricing->updatedAt();
        $minutes = (int) config('gold.stale_minutes');
        if ($at && $at->gt(now()->subMinutes($minutes))) {
            return 'Prices are fresh.';
        }

        if (! $this->pricing->halted()) {
            $this->halt("مفيش سعر جديد من {$minutes} دقيقة.");
        }

        return "No price for {$minutes} minutes. Online orders halted.";
    }

    private function halt(string $reason): void
    {
        Setting::put('trading_halted', true);
        Setting::put('halt_reason', $reason);
    }
}
