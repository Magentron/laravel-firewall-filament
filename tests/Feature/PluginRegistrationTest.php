<?php

namespace Magentron\LaravelFirewallFilament\Tests\Feature;

use Filament\Panel;
use Magentron\LaravelFirewallFilament\FirewallFilamentPlugin;
use Magentron\LaravelFirewallFilament\Pages\FirewallSettingsPage;
use Magentron\LaravelFirewallFilament\Pages\FirewallStatusPage;
use Magentron\LaravelFirewallFilament\Resources\AuditLogResource;
use Magentron\LaravelFirewallFilament\Resources\FirewallRuleResource;
use Magentron\LaravelFirewallFilament\Tests\TestCase;
use Magentron\LaravelFirewallFilament\Widgets\RecentLogLinesWidget;
use Magentron\LaravelFirewallFilament\Widgets\RuleCountsWidget;
use Mockery;

class PluginRegistrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_plugin_id(): void
    {
        $plugin = FirewallFilamentPlugin::make();
        $this->assertSame('magentron-laravel-firewall-filament', $plugin->getId());
    }

    public function test_registers_default_resources_and_pages(): void
    {
        $plugin = FirewallFilamentPlugin::make();

        $registeredResources = [];
        $registeredPages = [];
        $registeredWidgets = [];

        $panel = Mockery::mock(Panel::class);
        $panel->shouldReceive('resources')->once()->with(Mockery::on(function ($resources) use (&$registeredResources) {
            $registeredResources = $resources;
            return true;
        }))->andReturnSelf();
        $panel->shouldReceive('pages')->once()->with(Mockery::on(function ($pages) use (&$registeredPages) {
            $registeredPages = $pages;
            return true;
        }))->andReturnSelf();
        $panel->shouldReceive('widgets')->once()->with(Mockery::on(function ($widgets) use (&$registeredWidgets) {
            $registeredWidgets = $widgets;
            return true;
        }))->andReturnSelf();

        $plugin->register($panel);

        $this->assertContains(FirewallRuleResource::class, $registeredResources);
        $this->assertContains(AuditLogResource::class, $registeredResources);
        $this->assertContains(FirewallStatusPage::class, $registeredPages);
        $this->assertNotContains(FirewallSettingsPage::class, $registeredPages);
        $this->assertEmpty($registeredWidgets);
    }

    public function test_registers_settings_page_when_enabled(): void
    {
        $plugin = FirewallFilamentPlugin::make()->enableSettings();

        $registeredPages = [];

        $panel = Mockery::mock(Panel::class);
        $panel->shouldReceive('resources')->andReturnSelf();
        $panel->shouldReceive('pages')->with(Mockery::on(function ($pages) use (&$registeredPages) {
            $registeredPages = $pages;
            return true;
        }))->andReturnSelf();
        $panel->shouldReceive('widgets')->andReturnSelf();

        $plugin->register($panel);

        $this->assertContains(FirewallSettingsPage::class, $registeredPages);
    }

    public function test_registers_widgets_when_enabled(): void
    {
        $plugin = FirewallFilamentPlugin::make()->enableWidgets();

        $registeredWidgets = [];

        $panel = Mockery::mock(Panel::class);
        $panel->shouldReceive('resources')->andReturnSelf();
        $panel->shouldReceive('pages')->andReturnSelf();
        $panel->shouldReceive('widgets')->with(Mockery::on(function ($widgets) use (&$registeredWidgets) {
            $registeredWidgets = $widgets;
            return true;
        }))->andReturnSelf();

        $plugin->register($panel);

        $this->assertContains(RuleCountsWidget::class, $registeredWidgets);
        $this->assertContains(RecentLogLinesWidget::class, $registeredWidgets);
    }

    public function test_individual_widgets_can_be_disabled(): void
    {
        $plugin = FirewallFilamentPlugin::make()
            ->enableWidgets()
            ->enableRuleCountsWidget(false);

        $registeredWidgets = [];

        $panel = Mockery::mock(Panel::class);
        $panel->shouldReceive('resources')->andReturnSelf();
        $panel->shouldReceive('pages')->andReturnSelf();
        $panel->shouldReceive('widgets')->with(Mockery::on(function ($widgets) use (&$registeredWidgets) {
            $registeredWidgets = $widgets;
            return true;
        }))->andReturnSelf();

        $plugin->register($panel);

        $this->assertNotContains(RuleCountsWidget::class, $registeredWidgets);
        $this->assertContains(RecentLogLinesWidget::class, $registeredWidgets);
    }

    public function test_fluent_configuration_methods(): void
    {
        $plugin = FirewallFilamentPlugin::make()
            ->navigationGroup('Security')
            ->slug('fw');

        $this->assertSame('Security', $plugin->getNavigationGroup());
        $this->assertSame('fw', $plugin->getSlug());
    }
}
