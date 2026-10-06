<?php

namespace App\Services;

use App\Enums\Category;
use App\Models\GoldPrice;
use App\Models\KaratMargin;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Turns the stored 24k spot price into the customer prices we quote:
 * per-gram sell/buy for 24, 21 and 18 karat, and per-piece for the gold pound.
 */
class GoldPricing
{
    public const KARATS = ['24', '21', '18', 'coin'];

    private ?GoldPrice $latest = null;

    private ?array $margins = null;

    public function latest(): ?GoldPrice
    {
        return $this->latest ??= GoldPrice::query()->latest('recorded_at')->latest('id')->first();
    }

    public function base(): float
    {
        return $this->latest()?->base_24 ?? 0.0;
    }

    public function updatedAt(): ?Carbon
    {
        return $this->latest()?->recorded_at;
    }

    public function halted(): bool
    {
        return Setting::bool('trading_halted');
    }

    /** Spot value of one unit (gram, or one coin) for a karat key, before margins. */
    public function spot(string $karat, ?float $base = null): float
    {
        $base ??= $this->base();
        if ($karat === 'coin') {
            return config('gold.coin_grams') * $base * config('gold.coin_karat') / 24;
        }

        return $base * ((int) $karat) / 24;
    }

    public function sell(string $karat, ?float $base = null): int
    {
        return $this->round($this->spot($karat, $base) + $this->margin($karat, 'sell'));
    }

    public function buy(string $karat, ?float $base = null): int
    {
        return $this->round($this->spot($karat, $base) - $this->margin($karat, 'buy'));
    }

    /**
     * All quotes with the change over the last 24 hours.
     *
     * @return array<string, array{label: string, unit: string, sell: int, buy: int, change: float}>
     */
    public function quotes(): array
    {
        $previous = GoldPrice::query()
            ->where('recorded_at', '<=', ($this->updatedAt() ?? now())->copy()->subDay())
            ->latest('recorded_at')->value('base_24');

        $labels = ['24' => 'عيار 24', '21' => 'عيار 21', '18' => 'عيار 18', 'coin' => 'الجنيه الذهب'];
        $out = [];
        foreach (self::KARATS as $k) {
            $sell = $this->sell($k);
            $prev = $previous ? $this->sell($k, (float) $previous) : $sell;
            $out[$k] = [
                'label' => $labels[$k],
                'unit' => $k === 'coin' ? 'ج.م للجنيه' : 'ج.م للجرام',
                'sell' => $sell,
                'buy' => $this->buy($k),
                'change' => $prev ? round(($sell - $prev) / $prev * 100, 1) : 0.0,
            ];
        }

        return $out;
    }

    /** What the site header, the app and the API all share. */
    public function payload(): array
    {
        return [
            'quotes' => $this->quotes(),
            'updated_at' => $this->updatedAt()?->toIso8601String(),
            'halted' => $this->halted(),
        ];
    }

    /** Value of the gold in a piece at today's sell price, without the making fee. */
    public function goldValue(Product $product): int
    {
        if ($product->category === Category::Coin) {
            return $this->sell('coin');
        }

        return (int) round((float) $product->weight_g * $this->sell((string) $product->karat));
    }

    public function productPrice(Product $product): int
    {
        return $this->round($this->goldValue($product) + (float) $product->making_fee, 10);
    }

    /** What we pay for old gold of a karat and weight, before inspection. */
    public function buyBack(int $karat, float $grams): int
    {
        return (int) round($grams * $this->buy((string) $karat));
    }

    /**
     * Sell price series for a karat over a period, oldest first.
     *
     * @return list<array{t: string, v: int}>
     */
    public function history(string $karat, string $period): array
    {
        [$from, $step] = match ($period) {
            '24h' => [now()->subDay(), 1],
            '7d' => [now()->subDays(7), 4],
            default => [now()->subDays(30), 12],
        };

        $rows = GoldPrice::query()->where('recorded_at', '>=', $from)->orderBy('recorded_at')->get(['base_24', 'recorded_at']);
        $points = [];

        // The price in force when the period starts, so a quiet feed still
        // draws a flat line instead of an empty one.
        $before = GoldPrice::query()->where('recorded_at', '<', $from)->latest('recorded_at')->latest('id')->first(['base_24']);
        if ($before) {
            $points[] = ['t' => $from->toIso8601String(), 'v' => $this->sell($karat, $before->base_24)];
        }
        foreach ($rows->values() as $i => $row) {
            if ($i % $step === 0 || $i === $rows->count() - 1) {
                $points[] = ['t' => $row->recorded_at->toIso8601String(), 'v' => $this->sell($karat, $row->base_24)];
            }
        }

        return $points;
    }

    public function forget(): void
    {
        $this->latest = null;
        $this->margins = null;
    }

    private function margin(string $karat, string $side): float
    {
        $this->margins ??= KaratMargin::all()->keyBy('karat')->all();
        $row = $this->margins[$karat] ?? null;

        return $row ? (float) $row->{$side.'_margin'} : 0.0;
    }

    private function round(float $value, ?int $step = null): int
    {
        $step ??= (int) config('gold.rounding', 5);

        return (int) (round($value / $step) * $step);
    }
}
