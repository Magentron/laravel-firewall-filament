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
use Magentron\LaravelFirewallFilament\Support\AuditLogger;
use Magentron\LaravelFirewallFilament\Support\LockoutDetector;

class ManageFirewallRules extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = FirewallRuleResource::class;

    protected string $view = 'firewall-filament::pages.manage-firewall-rules';

    public static function getResource(): string
    {
        return static::$resource;
    }

    public function getTitle(): string
    {
        return __('firewall-filament::firewall-filament.rules.title');
    }

    public function table(Table $table): Table
    {
        $allowMutations = $this->resolveAllowMutations();

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
                        'key' => $entry->source . ':' . $entry->ip_address,
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
                    ->label(__('firewall-filament::firewall-filament.rules.column.ip_address'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('list')
                    ->label(__('firewall-filament::firewall-filament.rules.column.list'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'whitelist' => 'success',
                        'blacklist' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('source')
                    ->label(__('firewall-filament::firewall-filament.rules.column.source'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'database' => 'info',
                        'config' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('updated_at')
                    ->label(__('firewall-filament::firewall-filament.rules.column.updated'))
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('list')
                    ->label(__('firewall-filament::firewall-filament.rules.filter.list'))
                    ->options([
                        'whitelist' => __('firewall-filament::firewall-filament.rules.filter.whitelist'),
                        'blacklist' => __('firewall-filament::firewall-filament.rules.filter.blacklist'),
                    ]),
            ])
            ->actions([
                TableAction::make('move')
                    ->label(__('firewall-filament::firewall-filament.rules.action.move'))
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalHeading(__('firewall-filament::firewall-filament.rules.action.move_heading'))
                    ->modalDescription(function (array $record): string {
                        $base = 'This will remove the entry and re-add it to the other list. There is no in-place edit in the upstream package.';
                        $newList = $record['whitelisted'] ? 'blacklist' : 'whitelist';

                        if ($newList === 'blacklist' && $this->wouldLockOut($record['ip_address'])) {
                            return "⚠️ WARNING: Moving {$record['ip_address']} to the blacklist may lock you out — this IP matches your current session IP. " . $base;
                        }

                        return $base;
                    })
                    ->action(function (array $record): void {
                        $this->assertMutationAllowed($record);

                        $adapter = app(RuleStoreAdapter::class);
                        $newWhitelisted = !$record['whitelisted'];
                        $adapter->move($record['ip_address'], $newWhitelisted);

                        $this->auditLog('move', $record['ip_address'], [
                            'list' => $record['whitelisted'] ? 'whitelist' : 'blacklist',
                        ], [
                            'list' => $newWhitelisted ? 'whitelist' : 'blacklist',
                        ]);

                        Notification::make()
                            ->title(__('firewall-filament::firewall-filament.rules.notification.moved'))
                            ->body("Moved {$record['ip_address']} to " . ($newWhitelisted ? 'whitelist' : 'blacklist'))
                            ->success()
                            ->send();
                    })
                    ->disabled(fn (array $record): bool => $this->mutationGuard($allowMutations, $record)[0])
                    ->tooltip(fn (array $record): ?string => $this->mutationGuard($allowMutations, $record)[1]),
                TableAction::make('delete')
                    ->label(__('firewall-filament::firewall-filament.rules.action.delete'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (array $record): void {
                        $this->assertMutationAllowed($record);

                        $adapter = app(RuleStoreAdapter::class);
                        $adapter->remove($record['ip_address']);

                        $this->auditLog('remove', $record['ip_address'], [
                            'list' => $record['whitelisted'] ? 'whitelist' : 'blacklist',
                        ]);

                        Notification::make()
                            ->title(__('firewall-filament::firewall-filament.rules.notification.deleted'))
                            ->body("Removed {$record['ip_address']}")
                            ->success()
                            ->send();
                    })
                    ->disabled(fn (array $record): bool => $this->mutationGuard($allowMutations, $record)[0])
                    ->tooltip(fn (array $record): ?string => $this->mutationGuard($allowMutations, $record)[1]),
            ])
            ->bulkActions([
                BulkAction::make('delete')
                    ->label(__('firewall-filament::firewall-filament.rules.action.bulk_delete'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $this->assertMutationAllowed();

                        $adapter = app(RuleStoreAdapter::class);
                        $count = 0;
                        $skipped = 0;

                        foreach ($records as $record) {
                            if (($record['source'] ?? null) === 'config') {
                                $skipped++;

                                continue;
                            }

                            $adapter->remove($record['ip_address']);
                            $this->auditLog('remove', $record['ip_address'], [
                                'list' => $record['whitelisted'] ? 'whitelist' : 'blacklist',
                            ]);
                            $count++;
                        }

                        Notification::make()
                            ->title(__('firewall-filament::firewall-filament.rules.notification.bulk_deleted'))
                            ->body("Removed {$count} rule(s)" . ($skipped > 0 ? ", skipped {$skipped}" : ''))
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
        $allowMutations = $this->resolveAllowMutations();

        return [
            Action::make('create')
                ->label(__('firewall-filament::firewall-filament.rules.action.new_rule'))
                ->icon('heroicon-o-plus')
                ->form([
                    TextInput::make('ip_address')
                        ->label(__('firewall-filament::firewall-filament.rules.field.ip_address'))
                        ->required()
                        ->helperText(__('firewall-filament::firewall-filament.rules.field.ip_address_helper'))
                        ->rules([
                            'required',
                            'string',
                            'max:255',
                            function () {
                                return function (string $attribute, $value, $fail) {
                                    if (!FirewallEntryRule::isValid($value)) {
                                        $fail(__('firewall-filament::firewall-filament.rules.field.ip_address_validation'));
                                    }
                                };
                            },
                        ]),
                    Toggle::make('whitelisted')
                        ->label(__('firewall-filament::firewall-filament.rules.field.whitelist'))
                        ->helperText(__('firewall-filament::firewall-filament.rules.field.whitelist_helper'))
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $this->assertMutationAllowed();

                    if (!$data['whitelisted'] && $this->wouldLockOut($data['ip_address'])) {
                        Notification::make()
                            ->title(__('firewall-filament::firewall-filament.rules.notification.lockout_prevented'))
                            ->body(__('firewall-filament::firewall-filament.rules.notification.lockout_body', ['ip' => $data['ip_address']]))
                            ->danger()
                            ->send();

                        return;
                    }

                    $adapter = app(RuleStoreAdapter::class);
                    $adapter->add($data['ip_address'], $data['whitelisted']);

                    $this->auditLog('add', $data['ip_address'], null, [
                        'list' => $data['whitelisted'] ? 'whitelist' : 'blacklist',
                    ]);

                    Notification::make()
                        ->title(__('firewall-filament::firewall-filament.rules.notification.created'))
                        ->body("Added {$data['ip_address']} to " . ($data['whitelisted'] ? 'whitelist' : 'blacklist'))
                        ->success()
                        ->send();
                })
                ->visible($allowMutations)
                ->tooltip(fn () => !$allowMutations ? __('firewall-filament::firewall-filament.rules.config_mode.tooltip') : null),
            Action::make('clearAll')
                ->label(__('firewall-filament::firewall-filament.rules.action.clear_all'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('firewall-filament::firewall-filament.rules.action.clear_all_heading'))
                ->modalDescription(__('firewall-filament::firewall-filament.rules.action.clear_all_description'))
                ->modalSubmitActionLabel(__('firewall-filament::firewall-filament.rules.action.clear_all_confirm'))
                ->action(function (): void {
                    $this->assertMutationAllowed();

                    $adapter = app(RuleStoreAdapter::class);
                    $allRules = $adapter->all();
                    $before = [];
                    $skipped = 0;

                    foreach ($allRules as $entry) {
                        if ($entry->source === 'config') {
                            $skipped++;

                            continue;
                        }

                        $adapter->remove($entry->ip_address);
                        $before[] = [
                            'ip' => $entry->ip_address,
                            'list' => $entry->whitelisted ? 'whitelist' : 'blacklist',
                        ];
                    }

                    if (count($before) > 0) {
                        $this->auditLog('clear', null, $before);
                    }

                    Notification::make()
                        ->title(__('firewall-filament::firewall-filament.rules.notification.cleared'))
                        ->body('Removed ' . count($before) . ' rule(s)' . ($skipped > 0 ? ", skipped {$skipped}" : ''))
                        ->success()
                        ->send();
                })
                ->visible($allowMutations),
        ];
    }

    public function isConfigMode(): bool
    {
        return !config('firewall.use_database');
    }

    protected function resolveAllowMutations(): bool
    {
        $plugin = FirewallRuleResource::getPlugin();
        $isConfigMode = !config('firewall.use_database');

        return $plugin->can('mutateRules')
            && (!$isConfigMode || $plugin->allowsConfigModeMutations());
    }

    /**
     * @return array{0: bool, 1: ?string}
     */
    protected function mutationGuard(bool $allowMutations, array $record): array
    {
        if (($record['source'] ?? null) === 'config') {
            return [true, __('firewall-filament::firewall-filament.rules.config_sourced.tooltip')];
        }

        if (!$allowMutations) {
            return [true, __('firewall-filament::firewall-filament.rules.config_mode.tooltip')];
        }

        return [false, null];
    }

    protected function assertMutationAllowed(?array $record = null): void
    {
        if (!$this->resolveAllowMutations()) {
            abort(403);
        }

        if ($record !== null && ($record['source'] ?? null) === 'config') {
            abort(403);
        }
    }

    private function wouldLockOut(string $target): bool
    {
        try {
            $requestIp = request()->ip();
        } catch (\Throwable) {
            return false;
        }

        return LockoutDetector::wouldLockOut($target, $requestIp);
    }

    private function auditLog(string $action, ?string $target, mixed $before = null, mixed $after = null): void
    {
        try {
            $logger = app(AuditLogger::class);
            $logger->log(
                AuditLogger::currentUserId(),
                'mutateRules',
                $action,
                $target,
                $before,
                $after,
            );
        } catch (\Throwable) {
            // Audit failure must not break the primary operation
        }
    }
}
