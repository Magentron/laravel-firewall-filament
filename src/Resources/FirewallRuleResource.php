<?php

namespace Magentron\LaravelFirewallFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Magentron\LaravelFirewallFilament\Adapters\RuleEntry;
use Magentron\LaravelFirewallFilament\FirewallFilamentPlugin;
use Magentron\LaravelFirewallFilament\Resources\FirewallRuleResource\Pages;

class FirewallRuleResource extends Resource
{
    protected static ?string $model = null;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Firewall Rules';

    protected static ?string $modelLabel = 'Firewall Rule';

    protected static ?string $pluralModelLabel = 'Firewall Rules';

    public static function getModel(): string
    {
        return RuleEntry::class;
    }

    public static function getSlug(): string
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
