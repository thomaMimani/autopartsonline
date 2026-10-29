<?php

namespace App\Providers;

use App\Catalog\ApiProfileCatalogClient;
use App\Catalog\CatalogClient;
use App\Catalog\FakeCatalogClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CatalogClient::class, fn () => match (config('catalog.driver')) {
            'api' => new ApiProfileCatalogClient(
                (string) config('catalog.base_url'),
                (string) config('catalog.api_key'),
                config('catalog.lang_id'),
                config('catalog.country_filter_id'),
                config('catalog.type_id'),
                config('catalog.timeout'),
            ),
            default => new FakeCatalogClient,
        });
    }

    public function boot(): void
    {
        //
    }
}
