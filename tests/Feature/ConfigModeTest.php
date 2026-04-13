<?php

namespace Magentron\LaravelFirewallFilament\Tests\Feature;

use Magentron\LaravelFirewallFilament\Adapters\ConfigRuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Adapters\DatabaseRuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Adapters\RuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Tests\TestCase;

class ConfigModeTest extends TestCase
{
    public function test_config_mode_binds_config_adapter(): void
    {
        $this->app['config']->set('firewall.use_database', false);

        $adapter = $this->app->make(RuleStoreAdapter::class);
        $this->assertInstanceOf(ConfigRuleStoreAdapter::class, $adapter);
    }

    public function test_database_mode_binds_database_adapter(): void
    {
        $this->app['config']->set('firewall.use_database', true);

        $adapter = $this->app->make(RuleStoreAdapter::class);
        $this->assertInstanceOf(DatabaseRuleStoreAdapter::class, $adapter);
    }

    public function test_config_adapter_has_warning_banner(): void
    {
        $adapter = new ConfigRuleStoreAdapter();
        $warning = $adapter->warning();

        $this->assertNotEmpty($warning);
        $this->assertStringContainsString('config mode', $warning);
        $this->assertStringContainsString('NOT persisted', $warning);
    }

    public function test_config_adapter_warning_is_constant(): void
    {
        $this->assertSame(
            'Firewall is running in config mode. Changes are NOT persisted beyond the current process.',
            ConfigRuleStoreAdapter::NOT_PERSISTED_WARNING
        );
    }
}
