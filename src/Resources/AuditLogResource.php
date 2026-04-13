<?php

namespace Magentron\LaravelFirewallFilament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Magentron\LaravelFirewallFilament\FirewallFilamentPlugin;
use Magentron\LaravelFirewallFilament\Models\AuditLog;
use Magentron\LaravelFirewallFilament\Resources\AuditLogResource\Pages;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getNavigationLabel(): string
    {
        return __('firewall-filament::firewall-filament.audit.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('firewall-filament::firewall-filament.audit.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('firewall-filament::firewall-filament.audit.plural_model_label');
    }

    public static function getSlug(?\Filament\Panel $panel = null): string
    {
        return static::getPlugin()->getSlug() . '/audit';
    }

    public static function getNavigationGroup(): ?string
    {
        return static::getPlugin()->getNavigationGroup();
    }

    public static function getPlugin(): FirewallFilamentPlugin
    {
        /** @var FirewallFilamentPlugin */
        return filament()->getCurrentPanel()?->getPlugin('magentron-laravel-firewall-filament')
            ?? FirewallFilamentPlugin::make();
    }

    public static function canAccess(): bool
    {
        return static::getPlugin()->can('viewSettings');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('firewall-filament::firewall-filament.audit.column.date'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user_id')
                    ->label(__('firewall-filament::firewall-filament.audit.column.user_id'))
                    ->sortable(),
                TextColumn::make('ability')
                    ->label(__('firewall-filament::firewall-filament.audit.column.ability'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('action')
                    ->label(__('firewall-filament::firewall-filament.audit.column.action'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'add' => 'success',
                        'remove', 'clear' => 'danger',
                        'move' => 'warning',
                        'settings_change', 'settings_restore' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('target')
                    ->label(__('firewall-filament::firewall-filament.audit.column.target'))
                    ->limit(50),
                TextColumn::make('before')
                    ->label(__('firewall-filament::firewall-filament.audit.column.before'))
                    ->formatStateUsing(fn ($state) => $state !== null ? json_encode($state, JSON_UNESCAPED_SLASHES) : '—')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('after')
                    ->label(__('firewall-filament::firewall-filament.audit.column.after'))
                    ->formatStateUsing(fn ($state) => $state !== null ? json_encode($state, JSON_UNESCAPED_SLASHES) : '—')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options([
                        'add' => __('firewall-filament::firewall-filament.audit.filter.action.add'),
                        'remove' => __('firewall-filament::firewall-filament.audit.filter.action.remove'),
                        'move' => __('firewall-filament::firewall-filament.audit.filter.action.move'),
                        'clear' => __('firewall-filament::firewall-filament.audit.filter.action.clear'),
                        'settings_change' => __('firewall-filament::firewall-filament.audit.filter.action.settings_change'),
                        'settings_restore' => __('firewall-filament::firewall-filament.audit.filter.action.settings_restore'),
                    ]),
                SelectFilter::make('ability')
                    ->options([
                        'mutateRules' => __('firewall-filament::firewall-filament.audit.filter.ability.mutate_rules'),
                        'mutateSettings' => __('firewall-filament::firewall-filament.audit.filter.ability.mutate_settings'),
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
