<?php

use App\Contracts\PriceSource;
use App\Models\GoldPrice;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('prices:refresh', function (PriceSource $source) {
    $base = $source->fetchBase24();
    if ($base === null) {
        $this->info("No new price from the {$source->name()} source.");

        return;
    }
    GoldPrice::create(['base_24' => $base, 'source' => $source->name(), 'recorded_at' => now()]);
    $this->info("Saved 24k base price: {$base} EGP/g");
})->purpose('Fetch the latest 24k gold price from the configured source');

Schedule::command('prices:refresh')->everyMinute()->withoutOverlapping()
    ->when(fn () => config('gold.source') !== 'manual');
