<?php

namespace Magentron\LaravelFirewallFilament\Resources\FirewallRuleResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Magentron\LaravelFirewallFilament\Adapters\RuleEntry;
use Magentron\LaravelFirewallFilament\Adapters\RuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Resources\FirewallRuleResource;
use Magentron\LaravelFirewallFilament\Rules\FirewallEntryRule;

class ManageFirewallRules extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = FirewallRuleResource::class;

    protected static string $view = 'firewall-filament::pages.manage-firewall-rules';

    public static function getResource(): string
    {
        return static::$resource;
    }

    public function getTitle(): string
    {
        return 'Firewall Rules';
    }

    public function table(Table $table): Table
    {
        $isConfigMode = !config('firewall.use_database');
        $allowMutations = !$isConfigMode || FirewallRuleResource::getPlugin()->allowsConfigModeMutations();

        return $table
            ->records(function (
                ?string $search,
                ?string $sortColumn,
                ?string $sortDirection,
                ?array $filters,
            ) {
                $adapter = app(RuleStoreAdapter::class);
                $records = $adapter->all()->map(function (RuleEntry $entry) {
                    return [
                        'key' => $entry->ip_address,
                        'ip_address' => $entry->ip_address,
                        'whitelisted' => $entry->whitelisted,
                        'list' => $entry->whitelisted ? 'whitelist' : 'blacklist',
                        'source' => $entry->source,
                        'updated_at' => null,
                    ];
                });

                if (!empty($filters['list']['value'])) {
                    $listFilter = $filters['list']['value'];
                    $records = $records->filter(fn (array $record) => $record['list'] === $listFilter);
                }

                if ($search) {
                    $search = strtolower($search);
                    $records = $records->filter(function (array $record) use ($search) {
                        return str_contains(strtolower($record['ip_address']), $search);
                    });
                }

                if ($sortColumn && $sortDirection) {
                    $records = $records->sortBy($sortColumn, SORT_REGULAR, $sortDirection === 'desc');
                }

                return $records->values();
            })
            ->columns([
                TextColumn::make('ip_address')
                    ->label('IP / CIDR / Range / Pattern')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('list')
                    ->label('List')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'whitelist' => 'success',
                        'blacklist' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'database' => 'info',
                        'config' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('list')
                    ->label('List')
                    ->options([
                        'whitelist' => 'Whitelist',
                        'blacklist' => 'Blacklist',
                    ]),
            ])
            ->actions([
                TableAction::make('move')
                    ->label('Move to other list')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalHeading('Move to other list')
                    ->modalDescription('This will remove the entry and re-add it to the other list. There is no in-place edit in the upstream package.')
                    ->action(function (array $record): void {
                        $adapter = app(RuleStoreAdapter::class);
                        $newWhitelisted = !$record['whitelisted'];
                        $adapter->move($record['ip_address'], $newWhitelisted);

                        Notification::make()
                            ->title('Rule moved')
                            ->body("Moved {$record['ip_address']} to " . ($newWhitelisted ? 'whitelist' : 'blacklist'))
                            ->success()
                            ->send();
                    })
                    ->disabled(!$allowMutations)
                    ->tooltip(!$allowMutations ? 'Mutations are disabled in config mode. Enable firewall.use_database to persist changes.' : null),
                TableAction::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (array $record): void {
                        $adapter = app(RuleStoreAdapter::class);
                        $adapter->remove($record['ip_address']);

                        Notification::make()
                            ->title('Rule deleted')
                            ->body("Removed {$record['ip_address']}")
                            ->success()
                            ->send();
                    })
                    ->disabled(!$allowMutations)
                    ->tooltip(!$allowMutations ? 'Mutations are disabled in config mode. Enable firewall.use_database to persist changes.' : null),
            ])
            ->bulkActions([
                BulkAction::make('delete')
                    ->label('Delete selected')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $adapter = app(RuleStoreAdapter::class);
                        $count = 0;

                        foreach ($records as $record) {
                            $adapter->remove($record['ip_address']);
                            $count++;
                        }

                        Notification::make()
                            ->title('Rules deleted')
                            ->body("Removed {$count} rule(s)")
                            ->success()
                            ->send();
                    })
                    ->disabled(!$allowMutations),
            ])
            ->recordUrl(null)
            ->paginated(false);
    }

    protected function getHeaderActions(): array
    {
        $isConfigMode = !config('firewall.use_database');
        $allowMutations = !$isConfigMode || FirewallRuleResource::getPlugin()->allowsConfigModeMutations();

        return [
            Action::make('create')
                ->label('New rule')
                ->icon('heroicon-o-plus')
                ->form([
                    TextInput::make('ip_address')
                        ->label('IP Address / Pattern')
                        ->required()
                        ->helperText('Accepts: single IP, CIDR (e.g. 10.0.0.0/24), range (e.g. 10.0.0.1-10.0.0.255), country:XX, or host:domain.com. File-path entries are NOT accepted via the UI for security reasons; use the config file instead.')
                        ->rules([
                            'required',
                            'string',
                            'max:255',
                            function () {
                                return function (string $attribute, $value, $fail) {
                                    if (!FirewallEntryRule::isValid($value)) {
                                        $fail('The :attribute must be a valid IP address, CIDR notation, IP range, country:XX code, or host:domain pattern.');
                                    }
                                };
                            },
                        ]),
                    Toggle::make('whitelisted')
                        ->label('Whitelist')
                        ->helperText('Enable to add to the whitelist, disable to add to the blacklist.')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $adapter = app(RuleStoreAdapter::class);
                    $adapter->add($data['ip_address'], $data['whitelisted']);

                    Notification::make()
                        ->title('Rule created')
                        ->body("Added {$data['ip_address']} to " . ($data['whitelisted'] ? 'whitelist' : 'blacklist'))
                        ->success()
                        ->send();
                })
                ->visible($allowMutations)
                ->tooltip(fn () => !$allowMutations ? 'Mutations are disabled in config mode. Enable firewall.use_database to persist changes.' : null),
        ];
    }

    public function isConfigMode(): bool
    {
        return !config('firewall.use_database');
    }
}
