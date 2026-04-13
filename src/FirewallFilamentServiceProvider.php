<?php

namespace Magentron\LaravelFirewallFilament;

use Illuminate\Support\ServiceProvider;
use Magentron\LaravelFirewallFilament\Adapters\ConfigRuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Adapters\DatabaseRuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Adapters\LaravelLogFileAdapter;
use Magentron\LaravelFirewallFilament\Adapters\LogSourceAdapter;
use Magentron\LaravelFirewallFilament\Adapters\NullLogSourceAdapter;
use Magentron\LaravelFirewallFilament\Adapters\RuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Support\AuditLogger;
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

        $this->app->singleton(AuditLogger::class);

        $this->app->bind(RuleStoreAdapter::class, function () {
            return config('firewall.use_database')
                ? new DatabaseRuleStoreAdapter()
                : new ConfigRuleStoreAdapter();
        });

        $this->app->bind(LogSourceAdapter::class, function () {
            $logPath   = config('firewall-filament.log_file');
            $allowlist = (array) config('firewall-filament.log_file_allowlist', []);

            // Surface a misconfiguration warning: `log_file` is set but the
            // allowlist is empty, so the adapter will silently downgrade to
            // NullLogSourceAdapter and the operator will see "no logs" with
            // no obvious reason why. One log line per resolution is enough
            // to leave a breadcrumb without spamming the channel.
            if (null !== $logPath && '' !== $logPath && [] === $allowlist) {
                logger()->warning(
                    'firewall-filament: `log_file` is configured (' . $logPath . ') but `log_file_allowlist` is empty. '
                    . 'Log file reading is disabled. Add the absolute path to `log_file_allowlist` in config/firewall-filament.php to enable it.'
                );
            }

            if (null !== $logPath && '' !== $logPath && [] !== $allowlist) {
                return new LaravelLogFileAdapter($logPath, $allowlist);
            }

            return new NullLogSourceAdapter();
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'firewall-filament');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'firewall-filament');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->mergeFirewallSettings();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/firewall-filament.php' => config_path('firewall-filament.php'),
            ], 'firewall-filament-config');

            $this->publishes([
                __DIR__ . '/../resources/lang' => $this->app->langPath('vendor/firewall-filament'),
            ], 'firewall-filament-translations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/firewall-filament'),
            ], 'firewall-filament-views');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'firewall-filament-migrations');
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
