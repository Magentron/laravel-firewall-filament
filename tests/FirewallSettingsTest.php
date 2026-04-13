<?php

namespace Magentron\LaravelFirewallFilament\Tests;

use Magentron\LaravelFirewallFilament\Events\FirewallSettingsChanged;
use Magentron\LaravelFirewallFilament\FirewallFilamentPlugin;
use PHPUnit\Framework\TestCase;

class FirewallSettingsTest extends TestCase
{
    public function test_settings_disabled_by_default(): void
    {
        $plugin = FirewallFilamentPlugin::make();
        $this->assertFalse($plugin->hasSettings());
    }

    public function test_settings_can_be_enabled(): void
    {
        $plugin = FirewallFilamentPlugin::make()->enableSettings(true);
        $this->assertTrue($plugin->hasSettings());
    }

    public function test_settings_can_be_disabled_after_enabling(): void
    {
        $plugin = FirewallFilamentPlugin::make()
            ->enableSettings(true)
            ->enableSettings(false);
        $this->assertFalse($plugin->hasSettings());
    }

    public function test_enable_settings_is_fluent(): void
    {
        $plugin = FirewallFilamentPlugin::make();
        $result = $plugin->enableSettings(true);
        $this->assertSame($plugin, $result);
    }

    public function test_settings_changed_event_holds_data(): void
    {
        $settings = ['firewall.enable_log' => true, 'firewall.log_stack' => 'daily'];
        $previous = ['firewall.enable_log' => false, 'firewall.log_stack' => 'stack'];

        $event = new FirewallSettingsChanged($settings, $previous);

        $this->assertSame($settings, $event->settings);
        $this->assertSame($previous, $event->previous);
    }
}
