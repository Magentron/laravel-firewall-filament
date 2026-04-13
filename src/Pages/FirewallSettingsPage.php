<?php

namespace Magentron\LaravelFirewallFilament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Magentron\LaravelFirewallFilament\Events\FirewallSettingsChanged;
use Magentron\LaravelFirewallFilament\FirewallFilamentPlugin;
use Magentron\LaravelFirewallFilament\Support\AuditLogger;
use Magentron\LaravelFirewallFilament\Support\SettingsStore;

class FirewallSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function getNavigationLabel(): string
    {
        return __('firewall-filament::firewall-filament.settings.navigation_label');
    }

    public function getTitle(): string
    {
        return __('firewall-filament::firewall-filament.settings.title');
    }

    protected static string $view = 'firewall-filament::pages.firewall-settings';

    public bool $enable_log = false;

    public ?string $log_stack = null;

    public static function getSlug(): string
    {
        return static::getPlugin()->getSlug() . '/settings';
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
        return static::getPlugin()->can('viewSettings') && static::getPlugin()->hasSettings();
    }

    public function mount(): void
    {
        $store = app(SettingsStore::class);
        $saved = $store->get();

        $this->enable_log = $saved['firewall.enable_log'] ?? (bool) config('firewall.enable_log');
        $this->log_stack = $saved['firewall.log_stack'] ?? config('firewall.log_stack');

        $this->form->fill([
            'enable_log' => $this->enable_log,
            'log_stack' => $this->log_stack,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('firewall-filament::firewall-filament.settings.section.logging'))
                    ->description(__('firewall-filament::firewall-filament.settings.section.logging_description'))
                    ->schema([
                        Toggle::make('enable_log')
                            ->label(__('firewall-filament::firewall-filament.settings.field.enable_log'))
                            ->helperText(__('firewall-filament::firewall-filament.settings.field.enable_log_helper')),
                        TextInput::make('log_stack')
                            ->label(__('firewall-filament::firewall-filament.settings.field.log_stack'))
                            ->helperText(__('firewall-filament::firewall-filament.settings.field.log_stack_helper'))
                            ->nullable(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $store = app(SettingsStore::class);

        $previous = $store->get();

        $settings = [
            'firewall.enable_log' => (bool) ($data['enable_log'] ?? false),
            'firewall.log_stack' => $data['log_stack'] ?? null,
        ];

        $store->save($settings);

        config([
            'firewall.enable_log' => $settings['firewall.enable_log'],
            'firewall.log_stack' => $settings['firewall.log_stack'],
        ]);

        event(new FirewallSettingsChanged($settings, $previous));

        $this->auditLog('settings_change', null, $previous, $settings);

        Notification::make()
            ->title(__('firewall-filament::firewall-filament.settings.notification.saved'))
            ->success()
            ->send();
    }

    public function restoreSnapshot(string $snapshotId): void
    {
        $store = app(SettingsStore::class);

        try {
            $previous = $store->get();
            $restored = $store->restore($snapshotId);

            config([
                'firewall.enable_log' => $restored['firewall.enable_log'] ?? false,
                'firewall.log_stack' => $restored['firewall.log_stack'] ?? null,
            ]);

            $this->form->fill([
                'enable_log' => $restored['firewall.enable_log'] ?? false,
                'log_stack' => $restored['firewall.log_stack'] ?? null,
            ]);

            event(new FirewallSettingsChanged($restored, $previous));

            $this->auditLog('settings_restore', $snapshotId, $previous, $restored);

            Notification::make()
                ->title(__('firewall-filament::firewall-filament.settings.notification.restored'))
                ->body(__('firewall-filament::firewall-filament.settings.notification.restored_body', ['snapshot' => $snapshotId]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('firewall-filament::firewall-filament.settings.notification.restore_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getSnapshots(): array
    {
        return app(SettingsStore::class)->snapshots();
    }

    public function getSettingsFilePath(): string
    {
        return app(SettingsStore::class)->getSettingsFilePath();
    }

    public function canMutateSettings(): bool
    {
        return static::getPlugin()->can('mutateSettings');
    }

    private function auditLog(string $action, ?string $target, mixed $before = null, mixed $after = null): void
    {
        try {
            $logger = app(AuditLogger::class);
            $logger->log(
                AuditLogger::currentUserId(),
                'mutateSettings',
                $action,
                $target,
                $before,
                $after,
            );
        } catch (\Throwable) {
            // Audit failure must not break the primary operation
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('firewall-filament::firewall-filament.settings.action.save'))
                ->action('save')
                ->icon('heroicon-o-check')
                ->visible(fn () => $this->canMutateSettings()),
        ];
    }
}
