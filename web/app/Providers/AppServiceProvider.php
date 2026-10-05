<?php

namespace App\Providers;

use App\Contracts\PriceSource;
use App\PriceSources\HttpPriceSource;
use App\PriceSources\ManualPriceSource;
use App\Services\GoldPricing;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(GoldPricing::class);

        $this->app->bind(PriceSource::class, fn () => match (config('gold.source')) {
            'http' => new HttpPriceSource(config('gold.http')),
            default => new ManualPriceSource,
        });
    }

    public function boot(): void
    {
        Paginator::defaultView('components.pagination');

        View::composer(['components.layouts.site', 'site.*'], function ($view) {
            $view->with('prices', app(GoldPricing::class)->payload());
        });
    }
}
