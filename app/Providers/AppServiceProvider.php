<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interfaces\AddressProviderInterface;
use App\Services\ViaCepService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AddressProviderInterface::class, ViaCepService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
