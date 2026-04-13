<?php

namespace Magentron\LaravelFirewallFilament;

use Illuminate\Support\ServiceProvider;
use Magentron\LaravelFirewallFilament\Adapters\ConfigRuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Adapters\DatabaseRuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Adapters\LaravelLogFileAdapter;
use Magentron\LaravelFirewallFilament\Adapters\LogSourceAdapter;
use Magentron\LaravelFirewallFilament\Adapters\NullLogSourceAdapter;
use Magentron\LaravelFirewallFilament\Adapters\RuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Support\SettingsStore;

class FirewallFilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/firewall-filament.php',
            'firewall-filament'
        );

        $this->app->singleton(SettingsStore::class, function () {
            $settingsFile = config('firewall-filament.settings_file')
                ?? storage_path('app/firewall-filament-settings.json');
            $snapshotDir = config('firewall-filament.settings_snapshot_dir')
                ?? storage_path('app/firewall-filament-snapshots');

            return new SettingsStore($settingsFile, $snapshotDir);
        });

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
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'firewall-filament');

        $this->mergeFirewallSettings();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/firewall-filament.php' => config_path('firewall-filament.php'),
            ], 'firewall-filament-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/firewall-filament'),
            ], 'firewall-filament-views');
        }
    }

    private function mergeFirewallSettings(): void
    {
        $store = $this->app->make(SettingsStore::class);
        $settings = $store->get();

        foreach ($settings as $key => $value) {
            config([$key => $value]);
        }
    }
}
