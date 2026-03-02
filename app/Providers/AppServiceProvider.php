<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interfaces\AddressProviderInterface;
use App\Services\ViaCepService;
use App\Services\BrasilApiService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AddressProviderInterface::class, BrasilApiService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
