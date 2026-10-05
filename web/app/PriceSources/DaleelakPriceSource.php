<?php

namespace App\PriceSources;

use App\Contracts\PriceSource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gold prices from getdaleelak.com (GET /api/v1/feed.json, no key).
 *
 * Each asset carries the best licensed seller per direction under
 * directions.buy.best and directions.sell.best. Those are shop prices with the
 * shop's margin in them, so the base we store is their mid-point and our own
 * karat margins are applied on top as usual.
 */
class DaleelakPriceSource implements PriceSource
{
    private const ETAG_KEY = 'daleelak:etag';

    public function __construct(private array $config) {}

    public function name(): string
    {
        return 'daleelak';
    }

    public function fetchBase24(): ?float
    {
        $assets = $this->goldAssets(useEtag: true);
        if ($assets === null) {
            return null;
        }

        $asset = $this->pick24($assets);
        if (! $asset) {
            Log::warning('Daleelak: no 24k gold asset in the feed', ['wanted' => $this->config['asset_24'] ?? null, 'slugs' => array_column($assets, 'slug')]);

            return null;
        }

        return $asset['mid'];
    }

    /**
     * Gold assets in the feed, or null when there is nothing new or the call failed.
     *
     * @return list<array{slug: string, name: string, buy: ?float, sell: ?float, mid: ?float}>|null
     */
    public function goldAssets(bool $useEtag = false): ?array
    {
        $headers = ['Accept' => 'application/json'];
        if ($useEtag && ($etag = Cache::get(self::ETAG_KEY))) {
            $headers['If-None-Match'] = $etag;
        }

        try {
            $response = Http::timeout($this->config['timeout'] ?? 10)
                ->withHeaders($headers)
                ->get($this->config['url'], ['category' => 'gold']);
        } catch (\Throwable $e) {
            Log::warning('Daleelak: request failed', ['error' => $e->getMessage()]);

            return null;
        }

        if ($response->status() === 304) {
            return null;
        }
        if ($response->status() === 429) {
            Log::notice('Daleelak: rate limited', ['retry_after' => $response->header('Retry-After')]);

            return null;
        }
        if (! $response->successful() || ! is_array($response->json())) {
            Log::warning('Daleelak: bad response', ['status' => $response->status()]);

            return null;
        }

        if ($useEtag && $response->header('ETag')) {
            Cache::put(self::ETAG_KEY, $response->header('ETag'), now()->addDay());
        }

        $json = $response->json();
        $raw = Arr::get($json, 'data.assets', Arr::get($json, 'assets', []));

        return collect(is_array($raw) ? $raw : [])
            ->filter(fn ($a) => is_array($a))
            ->map(function (array $a) {
                $buy = $this->number(Arr::get($a, 'directions.buy.best'));
                $sell = $this->number(Arr::get($a, 'directions.sell.best'));
                $prices = array_filter([$buy, $sell]);

                return [
                    'slug' => (string) ($a['slug'] ?? $a['id'] ?? $a['code'] ?? ''),
                    'name' => $this->text($a['name'] ?? $a['title'] ?? ''),
                    'category' => (string) ($a['category'] ?? ''),
                    'buy' => $buy,
                    'sell' => $sell,
                    'mid' => $prices ? round(array_sum($prices) / count($prices), 2) : null,
                ];
            })
            ->filter(fn ($a) => $a['category'] === '' || str_contains(strtolower($a['category']), 'gold'))
            ->values()
            ->all();
    }

    /** The configured 24k asset, or the first one whose slug or name says 24. */
    public function pick24(array $assets): ?array
    {
        $wanted = $this->config['asset_24'] ?? null;
        $usable = array_values(array_filter($assets, fn ($a) => $a['mid'] !== null && $a['mid'] > 0));

        foreach ($usable as $a) {
            if ($wanted && $a['slug'] === $wanted) {
                return $a;
            }
        }
        foreach ($usable as $a) {
            if (preg_match('/(^|\D)24(\D|$)/u', $a['slug'].' '.$a['name'])) {
                return $a;
            }
        }

        return null;
    }

    /** "best" may be a bare number or an object holding the price. */
    private function number(mixed $value): ?float
    {
        if (is_array($value)) {
            $value = $value['price'] ?? $value['value'] ?? $value['rate'] ?? null;
        }

        return is_numeric($value) && $value > 0 ? (float) $value : null;
    }

    private function text(mixed $value): string
    {
        if (is_array($value)) {
            $value = $value['ar'] ?? $value['en'] ?? reset($value);
        }

        return (string) $value;
    }
}
