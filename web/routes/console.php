<?php

use App\Contracts\PriceSource;
use App\PriceSources\DaleelakPriceSource;
use App\Services\PriceFeed;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('prices:refresh {--fresh : Once the new price is saved, drop prices from other sources (e.g. the demo history)}', function (PriceSource $source, PriceFeed $feed) {
    $this->info($feed->refresh($source, (bool) $this->option('fresh')));
})->purpose('Fetch the latest 24k gold price from the configured source');

Artisan::command('prices:check-stale', function (PriceFeed $feed) {
    $this->info($feed->checkStale());
})->purpose('Halt online orders when the automatic price source stops updating');

Artisan::command('prices:probe', function () {
    $source = new DaleelakPriceSource(config('gold.daleelak'));
    $assets = $source->goldAssets();
    if ($assets === null) {
        $this->error('Could not read '.config('gold.daleelak.url').' (see storage/logs for the reason).');

        return 1;
    }

    $this->table(['slug', 'name', 'buy.best', 'sell.best', 'mid'], array_map(fn ($a) => [$a['slug'], $a['name'], $a['buy'], $a['sell'], $a['mid']], $assets));
    $pick = $source->pick24($assets);
    $pick
        ? $this->info("24k asset: {$pick['slug']} → base price {$pick['mid']} EGP/g")
        : $this->warn('No 24k asset found. Set DALEELAK_ASSET_24 to one of the slugs above.');

    return 0;
})->purpose('Read the Daleelak gold feed and show what would be used, without saving anything');

Schedule::command('prices:refresh')->everyMinute()->withoutOverlapping()
    ->when(fn () => config('gold.source') !== 'manual');
Schedule::command('prices:check-stale')->everyFiveMinutes()
    ->when(fn () => config('gold.source') !== 'manual');
