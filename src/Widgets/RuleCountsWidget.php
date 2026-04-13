<?php

namespace Magentron\LaravelFirewallFilament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Magentron\LaravelFirewallFilament\Adapters\RuleStoreAdapter;
use Magentron\LaravelFirewallFilament\FirewallFilamentPlugin;

class RuleCountsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $plugin = static::getPlugin();

        if ($plugin === null) {
            return false;
        }

        if (! $plugin->can('viewRules')) {
            return false;
        }

        if (! $plugin->hasWidgets()) {
            return false;
        }

        return $plugin->hasRuleCountsWidget();
    }

    protected function getStats(): array
    {
        $adapter = app(RuleStoreAdapter::class);
        $rules = $adapter->all();

        $whitelistCount = $rules->filter(fn ($r) => $r->whitelisted)->count();
        $blacklistCount = $rules->filter(fn ($r) => ! $r->whitelisted)->count();
        $total = $rules->count();
        $useDatabase = (bool) config('firewall.use_database');
        $storageMode = $useDatabase ? __('firewall-filament::firewall-filament.status.config.database') : __('firewall-filament::firewall-filament.status.config.config');

        return [
            Stat::make(__('firewall-filament::firewall-filament.widget.rule_counts.whitelist'), $whitelistCount)
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make(__('firewall-filament::firewall-filament.widget.rule_counts.blacklist'), $blacklistCount)
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
            Stat::make(__('firewall-filament::firewall-filament.widget.rule_counts.total'), $total)
                ->icon('heroicon-o-shield-check'),
            Stat::make(__('firewall-filament::firewall-filament.widget.rule_counts.storage'), $storageMode)
                ->icon('heroicon-o-circle-stack')
                ->color($useDatabase ? 'info' : 'warning'),
        ];
    }

    protected static function getPlugin(): ?FirewallFilamentPlugin
    {
        try {
            return Filament::getCurrentPanel()?->getPlugin('magentron-laravel-firewall-filament');
        } catch (\Exception) {
            return null;
        }
    }
}
