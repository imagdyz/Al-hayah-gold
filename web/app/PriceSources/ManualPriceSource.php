<?php

namespace App\PriceSources;

use App\Contracts\PriceSource;

/** Prices are typed in from the admin panel, so there is nothing to fetch. */
class ManualPriceSource implements PriceSource
{
    public function fetchBase24(): ?float
    {
        return null;
    }

    public function name(): string
    {
        return 'manual';
    }
}
