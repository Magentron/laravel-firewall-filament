<?php

namespace Magentron\LaravelFirewallFilament\Resources\FirewallRuleResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
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
use Throwable;

/**
 * Filament manage-records page for firewall rules.
 *
 * Provides the rules table with per-row move/delete actions, header
 * actions for create and clear-all, and a bulk-delete action. All
 * mutating actions are gated by two layers:
 *
 *   1. UI visibility / `->disabled()` — hides or greys out controls for
 *      users lacking `mutateRules`, users in config mode without the
 *      `allowConfigModeMutations` opt-in, and config-sourced rows.
 *   2. Server-side `abort(403)` inside each action closure — catches
 *      crafted Livewire requests that bypass the UI layer.
 *
 * The page builds `RuleEntry` snapshots from the in-memory row dictionary
 * so the adapter can short-circuit the config-sourced check without a
 * second `Firewall::find()` round-trip per call.
 */
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
                        'key'         => $entry->source . ':' . $entry->ip_address,
                        'ip_address'  => $entry->ip_address,
                        'whitelisted' => $entry->whitelisted,
                        'list'        => $entry->whitelisted ? 'whitelist' : 'blacklist',
                        'source'      => $entry->source,
                        'updated_at'  => null,
                    ];
                });

                // Apply list filter.
                if (!empty($filters['list']['value'])) {
                    $listFilter = $filters['list']['value'];
                    $records = $records->filter(fn (array $record) => $listFilter === $record['list']);
                }

                // Apply substring search on ip_address.
                if ($search) {
                    $needle  = strtolower($search);
                    $records = $records->filter(
                        fn (array $record) => str_contains(strtolower($record['ip_address']), $needle)
                    );
                }

                // Apply sort.
                if ($sortColumn && $sortDirection) {
                    $records = $records->sortBy($sortColumn, SORT_REGULAR, 'desc' === $sortDirection);
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
                        default     => 'gray',
                    }),
                TextColumn::make('source')
                    ->label(__('firewall-filament::firewall-filament.rules.column.source'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'database' => 'info',
                        'config'   => 'warning',
                        default    => 'gray',
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
                Action::make('move')
                    ->label(__('firewall-filament::firewall-filament.rules.action.move'))
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalHeading(__('firewall-filament::firewall-filament.rules.action.move_heading'))
                    ->modalDescription(function (array $record): string {
                        $base    = 'This will remove the entry and re-add it to the other list. There is no in-place edit in the upstream package.';
                        $newList = $record['whitelisted'] ? 'blacklist' : 'whitelist';

                        if ('blacklist' === $newList && $this->wouldLockOut($record['ip_address'])) {
                            return "⚠️ WARNING: Moving {$record['ip_address']} to the blacklist may lock you out — this IP matches your current session IP. " . $base;
                        }

                        return $base;
                    })
                    ->action(function (array $record): void {
                        $this->assertMutationAllowed($record);

                        $adapter        = app(RuleStoreAdapter::class);
                        $newWhitelisted = !$record['whitelisted'];
                        $knownEntry     = $this->recordToEntry($record);
                        $success        = $adapter->move($record['ip_address'], $newWhitelisted, $knownEntry);

                        if (! $success) {
                            Notification::make()
                                ->title(__('firewall-filament::firewall-filament.rules.notification.move_failed'))
                                ->body(__('firewall-filament::firewall-filament.rules.notification.move_failed_body', ['ip' => $record['ip_address']]))
                                ->danger()
                                ->send();

                            return;
                        }

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
                Action::make('delete')
                    ->label(__('firewall-filament::firewall-filament.rules.action.delete'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (array $record): void {
                        $this->assertMutationAllowed($record);

                        $adapter    = app(RuleStoreAdapter::class);
                        $knownEntry = $this->recordToEntry($record);
                        $success    = $adapter->remove($record['ip_address'], $knownEntry);

                        if (! $success) {
                            Notification::make()
                                ->title(__('firewall-filament::firewall-filament.rules.notification.delete_failed'))
                                ->body(__('firewall-filament::firewall-filament.rules.notification.delete_failed_body', ['ip' => $record['ip_address']]))
                                ->danger()
                                ->send();

                            return;
                        }

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
                        $removed = 0;
                        $skipped = 0;

                        foreach ($records as $record) {
                            if ('config' === ($record['source'] ?? null)) {
                                $skipped++;

                                continue;
                            }

                            $knownEntry = $this->recordToEntry($record);

                            if (! $adapter->remove($record['ip_address'], $knownEntry)) {
                                $skipped++;

                                continue;
                            }

                            $this->auditLog('remove', $record['ip_address'], [
                                'list' => $record['whitelisted'] ? 'whitelist' : 'blacklist',
                            ]);
                            $removed++;
                        }

                        Notification::make()
                            ->title(__('firewall-filament::firewall-filament.rules.notification.bulk_deleted'))
                            ->body("Removed {$removed} rule(s)" . ($skipped > 0 ? ", skipped {$skipped}" : ''))
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
                    $success = $adapter->add($data['ip_address'], $data['whitelisted']);

                    if (! $success) {
                        Notification::make()
                            ->title(__('firewall-filament::firewall-filament.rules.notification.create_failed'))
                            ->body(__('firewall-filament::firewall-filament.rules.notification.create_failed_body', ['ip' => $data['ip_address']]))
                            ->danger()
                            ->send();

                        return;
                    }

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

                    $adapter  = app(RuleStoreAdapter::class);
                    $allRules = $adapter->all();

                    $removedEntries = [];
                    $skipped        = 0;

                    foreach ($allRules as $entry) {
                        if ('config' === $entry->source) {
                            $skipped++;

                            continue;
                        }

                        if (! $adapter->remove($entry->ip_address, $entry)) {
                            $skipped++;

                            continue;
                        }

                        $removedEntries[] = [
                            'ip'   => $entry->ip_address,
                            'list' => $entry->whitelisted ? 'whitelist' : 'blacklist',
                        ];
                    }

                    if (count($removedEntries) > 0) {
                        $this->auditLog('clear', null, $removedEntries);
                    }

                    Notification::make()
                        ->title(__('firewall-filament::firewall-filament.rules.notification.cleared'))
                        ->body('Removed ' . count($removedEntries) . ' rule(s)' . ($skipped > 0 ? ", skipped {$skipped}" : ''))
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

    /**
     * Resolve whether the current user (in the current storage mode) may
     * run any mutating action on rules. Combines the `mutateRules` ability
     * with the `allowConfigModeMutations` opt-in.
     */
    protected function resolveAllowMutations(): bool
    {
        $plugin       = FirewallRuleResource::getPlugin();
        $isConfigMode = !config('firewall.use_database');

        return $plugin->can('mutateRules')
            && (!$isConfigMode || $plugin->allowsConfigModeMutations());
    }

    /**
     * Shared per-row guard for move/delete: returns a [disabled, tooltip]
     * pair so the `->disabled()` and `->tooltip()` closures stay in
     * lockstep. A row is disabled if either the global `$allowMutations`
     * is false OR the row came from config-array storage (read-only).
     *
     * @return array{0: bool, 1: ?string}
     */
    protected function mutationGuard(bool $allowMutations, array $record): array
    {
        $isConfigSourced = 'config' === ($record['source'] ?? null);

        if ($isConfigSourced) {
            return [true, __('firewall-filament::firewall-filament.rules.config_sourced.tooltip')];
        }

        if (! $allowMutations) {
            return [true, __('firewall-filament::firewall-filament.rules.config_mode.tooltip')];
        }

        return [false, null];
    }

    /**
     * Server-side gate mirrored inside every mutating action closure
     * (create, move, delete, bulk-delete, clearAll). Aborts with 403 if
     * the UI-level `->visible()` / `->disabled()` is bypassed via a
     * crafted Livewire request. When `$record` is supplied, also refuses
     * mutations on config-sourced rows.
     *
     * Calls `resolveAllowMutations()` LIVE (not the builder-time captured
     * bool) so a mid-session permission change is honoured immediately
     * on the next action invocation, without needing a page refresh.
     * The UI-level `->disabled()` / `->visible()` closures still use the
     * captured value — they are evaluated at render time and there is no
     * cheap way to re-render the table on every authorization tick — but
     * the server-side gate is the authoritative one.
     *
     * This is the single source of truth for action-level authorization
     * in this page; a focused regression test on this helper guards every
     * action closure that calls it, instead of needing to reflect into
     * each table-builder closure individually.
     */
    protected function assertMutationAllowed(?array $record = null): void
    {
        if (! $this->resolveAllowMutations()) {
            abort(403);
        }

        if (null !== $record && 'config' === ($record['source'] ?? null)) {
            abort(403);
        }
    }

    /**
     * Rebuild a `RuleEntry` from the table's in-memory row dictionary so
     * the adapter can skip a second `Firewall::find()` round-trip when
     * checking whether the row is config-sourced.
     */
    protected function recordToEntry(array $record): RuleEntry
    {
        return new RuleEntry(
            ip_address:  $record['ip_address'],
            whitelisted: (bool) $record['whitelisted'],
            source:      $record['source'] ?? 'config',
        );
    }

    /**
     * Return true if blacklisting (or moving to the blacklist) the given
     * IP/CIDR/range/country/host target would affect the current request's
     * IP. Used by the confirmation modal to surface a lock-out warning.
     */
    protected function wouldLockOut(string $target): bool
    {
        try {
            $requestIp = request()->ip();
        } catch (Throwable) {
            return false;
        }

        return LockoutDetector::wouldLockOut($target, $requestIp);
    }

    /**
     * Write an audit-log entry for a rule mutation. Audit failures are
     * swallowed so a broken audit sink cannot block the primary operation.
     */
    protected function auditLog(string $action, ?string $target, mixed $before = null, mixed $after = null): void
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
        } catch (Throwable) {
            // Audit failure must not break the primary operation.
        }
    }
}
