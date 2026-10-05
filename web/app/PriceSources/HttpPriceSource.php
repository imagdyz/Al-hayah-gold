<?php

namespace App\PriceSources;

use App\Contracts\PriceSource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reads the 24k gram price in EGP from a JSON API. Works with goldapi.io
 * (GET https://www.goldapi.io/api/XAU/EGP, path "price_gram_24k") or any
 * provider whose response holds that number at a known dot path.
 */
class HttpPriceSource implements PriceSource
{
    public function __construct(private array $config) {}

    public function fetchBase24(): ?float
    {
        if (empty($this->config['url'])) {
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(array_filter(['x-access-token' => $this->config['token'] ?? null]))
                ->acceptJson()
                ->get($this->config['url']);
        } catch (\Throwable $e) {
            Log::warning('Gold price fetch failed', ['error' => $e->getMessage()]);

            return null;
        }

        $value = Arr::get($response->json() ?? [], $this->config['path'] ?? 'price_gram_24k');

        return $response->successful() && is_numeric($value) && $value > 0 ? (float) $value : null;
    }

    public function name(): string
    {
        return 'http';
    }
}
