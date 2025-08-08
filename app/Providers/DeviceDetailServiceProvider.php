<?php

namespace App\Providers;

use App\Helpers\DeviceDetailHelper;
use Illuminate\Support\ServiceProvider;

class DeviceDetailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar el helper como un servicio singleton
        $this->app->singleton('device-detail-helper', function ($app) {
            return new DeviceDetailHelper($app->make('App\Services\Api\DeviceDetail'));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
