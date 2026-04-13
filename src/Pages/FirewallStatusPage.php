<?php

namespace Magentron\LaravelFirewallFilament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Magentron\LaravelFirewallFilament\Adapters\LogSourceAdapter;
use Magentron\LaravelFirewallFilament\Adapters\RuleStoreAdapter;
use Magentron\LaravelFirewallFilament\FirewallFilamentPlugin;
use Magentron\LaravelFirewallFilament\Support\LogEntryParser;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;

class FirewallStatusPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Firewall Status';

    protected static ?string $title = 'Firewall Status & Stats';

    protected static string $view = 'firewall-filament::pages.firewall-status';

    public static function getSlug(): string
    {
        return static::getPlugin()->getSlug() . '/status';
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
        return static::getPlugin()->isAuthorized();
    }

    public function getWhitelistCount(): int
    {
        return app(RuleStoreAdapter::class)->all()->filter(fn ($entry) => $entry->whitelisted)->count();
    }

    public function getBlacklistCount(): int
    {
        return app(RuleStoreAdapter::class)->all()->filter(fn ($entry) => !$entry->whitelisted)->count();
    }

    public function getTotalCount(): int
    {
        return app(RuleStoreAdapter::class)->all()->count();
    }

    public function getUseDatabase(): bool
    {
        return (bool) config('firewall.use_database');
    }

    public function getEnableLog(): bool
    {
        return (bool) config('firewall.enable_log');
    }

    public function getLogStack(): ?string
    {
        $value = config('firewall.log_stack');

        return is_string($value) ? $value : null;
    }

    public function getFirewallReport(): ?string
    {
        try {
            $report = Firewall::report();

            if (is_string($report) && $report !== '') {
                return $report;
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function isLogSupported(): bool
    {
        return app(LogSourceAdapter::class)->supported();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function () {
                if (!$this->isLogSupported()) {
                    return new Collection();
                }

                $adapter = app(LogSourceAdapter::class);
                $entries = $adapter->recentEntries(100);

                return collect($entries)->map(function (string $line, int $index) {
                    return [
                        'key' => $index,
                        'timestamp' => LogEntryParser::parseTimestamp($line),
                        'entry' => $line,
                    ];
                })->reverse()->values();
            })
            ->columns([
                TextColumn::make('timestamp')
                    ->label('Timestamp')
                    ->placeholder('—'),
                TextColumn::make('entry')
                    ->label('Log Entry')
                    ->wrap(),
            ])
            ->recordUrl(null)
            ->paginated(false)
            ->emptyStateHeading('No log entries')
            ->emptyStateDescription('No recent firewall log entries found.');
    }
}
