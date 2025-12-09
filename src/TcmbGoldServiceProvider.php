<?php

namespace DenizTezc\TcmbGold;

use Illuminate\Support\ServiceProvider;
use DenizTezc\TcmbGold\Services\GoldService;

class TcmbGoldServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/tcmb-gold.php' => config_path('tcmb-gold.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'migrations');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tcmb-gold.php', 'tcmb-gold');

        $this->app->bind('tcmb-gold', function ($app) {
            return new GoldService();
        });
    }
}
