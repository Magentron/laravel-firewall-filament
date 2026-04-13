<?php

namespace Magentron\LaravelFirewallFilament;

use Illuminate\Support\ServiceProvider;

class FirewallFilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/firewall-filament.php',
            'firewall-filament'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/firewall-filament.php' => config_path('firewall-filament.php'),
            ], 'firewall-filament-config');
        }
    }
}
