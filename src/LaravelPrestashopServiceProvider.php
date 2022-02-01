<?php

namespace Pdik\LaravelPrestaShop;

use Illuminate\Support\ServiceProvider;

class LaravelPrestashopServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerConfigs();
    }

    private function registerConfigs()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/prestashop.php' => config_path('laravel-prestashop.php'),
            ], 'laravel-prestashop-config');

        }
        $this->mergeConfigFrom(__DIR__.'/../config/prestashop.php', 'laravel-prestashop');

    }
}