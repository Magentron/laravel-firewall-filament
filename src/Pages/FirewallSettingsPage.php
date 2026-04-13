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
use Magentron\LaravelFirewallFilament\Support\SettingsStore;

class FirewallSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Firewall Settings';

    protected static ?string $title = 'Firewall Settings';

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
        return static::getPlugin()->isAuthorized() && static::getPlugin()->hasSettings();
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
                Section::make('Logging')
                    ->description('Configure firewall logging behaviour. Only these two settings are editable at runtime.')
                    ->schema([
                        Toggle::make('enable_log')
                            ->label('Enable Firewall Logging')
                            ->helperText('Toggle firewall request logging on or off.'),
                        TextInput::make('log_stack')
                            ->label('Log Stack / Channel')
                            ->helperText('The Laravel log channel to use for firewall entries (e.g. "stack", "single", "daily").')
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

        Notification::make()
            ->title('Settings saved')
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

            Notification::make()
                ->title('Settings restored')
                ->body("Restored snapshot from {$snapshotId}.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Restore failed')
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->action('save')
                ->icon('heroicon-o-check'),
        ];
    }
}
