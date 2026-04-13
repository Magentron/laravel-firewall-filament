<?php

namespace Magentron\LaravelFirewallFilament\Resources;

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

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Audit Trail';

    protected static ?string $modelLabel = 'Audit Entry';

    protected static ?string $pluralModelLabel = 'Audit Trail';

    public static function getSlug(): string
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
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user_id')
                    ->label('User ID')
                    ->sortable(),
                TextColumn::make('ability')
                    ->label('Ability')
                    ->badge()
                    ->sortable(),
                TextColumn::make('action')
                    ->label('Action')
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
                    ->label('Target')
                    ->limit(50),
                TextColumn::make('before')
                    ->label('Before')
                    ->formatStateUsing(fn ($state) => $state !== null ? json_encode($state, JSON_UNESCAPED_SLASHES) : '—')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('after')
                    ->label('After')
                    ->formatStateUsing(fn ($state) => $state !== null ? json_encode($state, JSON_UNESCAPED_SLASHES) : '—')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options([
                        'add' => 'Add',
                        'remove' => 'Remove',
                        'move' => 'Move',
                        'clear' => 'Clear',
                        'settings_change' => 'Settings Change',
                        'settings_restore' => 'Settings Restore',
                    ]),
                SelectFilter::make('ability')
                    ->options([
                        'mutateRules' => 'Mutate Rules',
                        'mutateSettings' => 'Mutate Settings',
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
