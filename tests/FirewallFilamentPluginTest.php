<?php

namespace Magentron\LaravelFirewallFilament\Tests;

use Magentron\LaravelFirewallFilament\FirewallFilamentPlugin;
use PHPUnit\Framework\TestCase;

class FirewallFilamentPluginTest extends TestCase
{
    private FirewallFilamentPlugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plugin = FirewallFilamentPlugin::make();
    }

    public function test_config_mode_mutations_disabled_by_default(): void
    {
        $this->assertFalse($this->plugin->allowsConfigModeMutations());
    }

    public function test_config_mode_mutations_can_be_enabled(): void
    {
        $this->plugin->allowConfigModeMutations(true);
        $this->assertTrue($this->plugin->allowsConfigModeMutations());
    }

    public function test_config_mode_mutations_can_be_disabled_after_enabling(): void
    {
        $this->plugin->allowConfigModeMutations(true);
        $this->plugin->allowConfigModeMutations(false);
        $this->assertFalse($this->plugin->allowsConfigModeMutations());
    }

    public function test_allow_config_mode_mutations_is_fluent(): void
    {
        $result = $this->plugin->allowConfigModeMutations(true);
        $this->assertSame($this->plugin, $result);
    }
}
