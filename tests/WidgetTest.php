<?php

namespace Magentron\LaravelFirewallFilament\Tests;

use Magentron\LaravelFirewallFilament\FirewallFilamentPlugin;
use PHPUnit\Framework\TestCase;

class WidgetTest extends TestCase
{
    private FirewallFilamentPlugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plugin = FirewallFilamentPlugin::make();
    }

    public function test_widgets_disabled_by_default(): void
    {
        $this->assertFalse($this->plugin->hasWidgets());
    }

    public function test_widgets_can_be_enabled(): void
    {
        $this->plugin->enableWidgets(true);
        $this->assertTrue($this->plugin->hasWidgets());
    }

    public function test_widgets_can_be_disabled(): void
    {
        $this->plugin->enableWidgets(true);
        $this->plugin->enableWidgets(false);
        $this->assertFalse($this->plugin->hasWidgets());
    }

    public function test_enable_widgets_is_fluent(): void
    {
        $result = $this->plugin->enableWidgets(true);
        $this->assertSame($this->plugin, $result);
    }

    public function test_rule_counts_widget_enabled_by_default(): void
    {
        $this->assertTrue($this->plugin->hasRuleCountsWidget());
    }

    public function test_rule_counts_widget_can_be_disabled(): void
    {
        $this->plugin->enableRuleCountsWidget(false);
        $this->assertFalse($this->plugin->hasRuleCountsWidget());
    }

    public function test_rule_counts_widget_can_be_re_enabled(): void
    {
        $this->plugin->enableRuleCountsWidget(false);
        $this->plugin->enableRuleCountsWidget(true);
        $this->assertTrue($this->plugin->hasRuleCountsWidget());
    }

    public function test_enable_rule_counts_widget_is_fluent(): void
    {
        $result = $this->plugin->enableRuleCountsWidget(false);
        $this->assertSame($this->plugin, $result);
    }

    public function test_recent_log_lines_widget_enabled_by_default(): void
    {
        $this->assertTrue($this->plugin->hasRecentLogLinesWidget());
    }

    public function test_recent_log_lines_widget_can_be_disabled(): void
    {
        $this->plugin->enableRecentLogLinesWidget(false);
        $this->assertFalse($this->plugin->hasRecentLogLinesWidget());
    }

    public function test_recent_log_lines_widget_can_be_re_enabled(): void
    {
        $this->plugin->enableRecentLogLinesWidget(false);
        $this->plugin->enableRecentLogLinesWidget(true);
        $this->assertTrue($this->plugin->hasRecentLogLinesWidget());
    }

    public function test_enable_recent_log_lines_widget_is_fluent(): void
    {
        $result = $this->plugin->enableRecentLogLinesWidget(false);
        $this->assertSame($this->plugin, $result);
    }

    public function test_per_widget_flags_independent_of_global_flag(): void
    {
        $this->plugin->enableWidgets(true);
        $this->plugin->enableRuleCountsWidget(false);
        $this->assertTrue($this->plugin->hasWidgets());
        $this->assertFalse($this->plugin->hasRuleCountsWidget());
        $this->assertTrue($this->plugin->hasRecentLogLinesWidget());
    }
}
