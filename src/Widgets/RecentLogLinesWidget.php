<?php

namespace Magentron\LaravelFirewallFilament\Widgets;

use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Magentron\LaravelFirewallFilament\Adapters\LogSourceAdapter;
use Magentron\LaravelFirewallFilament\FirewallFilamentPlugin;
use Magentron\LaravelFirewallFilament\Support\LogEntryParser;

class RecentLogLinesWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('firewall-filament::firewall-filament.widget.recent_log.heading');
    }

    public static function canView(): bool
    {
        $plugin = static::getPlugin();

        if ($plugin === null) {
            return false;
        }

        if (! $plugin->can('viewLogs')) {
            return false;
        }

        if (! $plugin->hasWidgets()) {
            return false;
        }

        if (! $plugin->hasRecentLogLinesWidget()) {
            return false;
        }

        $adapter = app(LogSourceAdapter::class);

        return $adapter->supported();
    }

    public function table(Table $table): Table
    {
        $adapter = app(LogSourceAdapter::class);
        $entries = [];
        $index = 0;

        foreach ($adapter->recentEntries(10) as $line) {
            $entries[] = [
                'id' => $index++,
                'timestamp' => LogEntryParser::parseTimestamp($line) ?? '-',
                'entry' => $line,
            ];
        }

        return $table
            ->records($entries)
            ->columns([
                TextColumn::make('timestamp')
                    ->label(__('firewall-filament::firewall-filament.widget.recent_log.column.time'))
                    ->width('180px'),
                TextColumn::make('entry')
                    ->label(__('firewall-filament::firewall-filament.widget.recent_log.column.entry'))
                    ->wrap(),
            ])
            ->paginated(false);
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
