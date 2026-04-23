<?php

namespace Magentron\LaravelFirewallFilament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Panel;
use Filament\Schemas\Schema;
use Magentron\LaravelFirewallFilament\Adapters\RuleEntry;
use Magentron\LaravelFirewallFilament\FirewallFilamentPlugin;
use Magentron\LaravelFirewallFilament\Resources\FirewallRuleResource\Pages;

class FirewallRuleResource extends Resource
{
    protected static ?string $model = null;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    public static function getNavigationLabel(): string
    {
        return __('firewall-filament::firewall-filament.rules.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('firewall-filament::firewall-filament.rules.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('firewall-filament::firewall-filament.rules.plural_model_label');
    }

    public static function getModel(): string
    {
        return RuleEntry::class;
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return static::getPlugin()->getSlug() . '/rules';
    }

    public static function getNavigationGroup(): ?string
    {
        return static::getPlugin()->getNavigationGroup();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFirewallRules::route('/'),
        ];
    }

    public static function getPlugin(): FirewallFilamentPlugin
    {
        /** @var FirewallFilamentPlugin */
        return filament()->getCurrentPanel()?->getPlugin('magentron-laravel-firewall-filament')
            ?? FirewallFilamentPlugin::make();
    }

    public static function canAccess(): bool
    {
        return static::getPlugin()->can('viewRules');
    }
}
