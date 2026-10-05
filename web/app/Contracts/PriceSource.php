<?php

namespace App\Contracts;

interface PriceSource
{
    /** EGP price of one gram of 24k gold, or null when the source has nothing new. */
    public function fetchBase24(): ?float;

    public function name(): string;
}
