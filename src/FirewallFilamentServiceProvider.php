<?php

namespace Magentron\LaravelFirewallFilament;

use Illuminate\Support\ServiceProvider;
use Magentron\LaravelFirewallFilament\Adapters\ConfigRuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Adapters\DatabaseRuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Adapters\LaravelLogFileAdapter;
use Magentron\LaravelFirewallFilament\Adapters\LogSourceAdapter;
use Magentron\LaravelFirewallFilament\Adapters\NullLogSourceAdapter;
use Magentron\LaravelFirewallFilament\Adapters\RuleStoreAdapter;

class FirewallFilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/firewall-filament.php',
            'firewall-filament'
        );

        $this->app->bind(RuleStoreAdapter::class, function () {
            return config('firewall.use_database')
                ? new DatabaseRuleStoreAdapter()
                : new ConfigRuleStoreAdapter();
        });

        $this->app->bind(LogSourceAdapter::class, function () {
            $logPath = config('firewall-filament.log_file');

            if ($logPath !== null && $logPath !== '') {
                return new LaravelLogFileAdapter($logPath);
            }

            return new NullLogSourceAdapter();
        });
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
