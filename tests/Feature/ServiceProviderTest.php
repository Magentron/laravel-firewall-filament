<?php

namespace Magentron\LaravelFirewallFilament\Tests\Feature;

use Magentron\LaravelFirewallFilament\Adapters\ConfigRuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Adapters\DatabaseRuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Adapters\LogSourceAdapter;
use Magentron\LaravelFirewallFilament\Adapters\NullLogSourceAdapter;
use Magentron\LaravelFirewallFilament\Adapters\RuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Support\AuditLogger;
use Magentron\LaravelFirewallFilament\Support\SettingsStore;
use Magentron\LaravelFirewallFilament\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_config_is_merged(): void
    {
        $this->assertNotNull($this->app['config']->get('firewall-filament'));
        $this->assertArrayHasKey('log_file', $this->app['config']->get('firewall-filament'));
        $this->assertArrayHasKey('settings_file', $this->app['config']->get('firewall-filament'));
    }

    public function test_settings_store_is_bound_as_singleton(): void
    {
        $store1 = $this->app->make(SettingsStore::class);
        $store2 = $this->app->make(SettingsStore::class);

        $this->assertInstanceOf(SettingsStore::class, $store1);
        $this->assertSame($store1, $store2);
    }

    public function test_audit_logger_is_bound_as_singleton(): void
    {
        $logger1 = $this->app->make(AuditLogger::class);
        $logger2 = $this->app->make(AuditLogger::class);

        $this->assertInstanceOf(AuditLogger::class, $logger1);
        $this->assertSame($logger1, $logger2);
    }

    public function test_config_adapter_bound_when_use_database_is_false(): void
    {
        $this->app['config']->set('firewall.use_database', false);

        $adapter = $this->app->make(RuleStoreAdapter::class);
        $this->assertInstanceOf(ConfigRuleStoreAdapter::class, $adapter);
    }

    public function test_database_adapter_bound_when_use_database_is_true(): void
    {
        $this->app['config']->set('firewall.use_database', true);

        $adapter = $this->app->make(RuleStoreAdapter::class);
        $this->assertInstanceOf(DatabaseRuleStoreAdapter::class, $adapter);
    }

    public function test_null_log_adapter_bound_when_no_log_file(): void
    {
        $this->app['config']->set('firewall-filament.log_file', null);

        $adapter = $this->app->make(LogSourceAdapter::class);
        $this->assertInstanceOf(NullLogSourceAdapter::class, $adapter);
    }

    public function test_views_are_loaded(): void
    {
        $this->assertTrue(
            $this->app['view']->exists('firewall-filament::pages.manage-firewall-rules')
        );
    }

    public function test_translations_are_loaded(): void
    {
        $translated = __('firewall-filament::firewall-filament.rules.navigation_label');
        $this->assertNotSame('firewall-filament::firewall-filament.rules.navigation_label', $translated);
    }
}
