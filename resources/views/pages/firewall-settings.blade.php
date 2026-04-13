<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" icon="heroicon-o-check">
                Save Settings
            </x-filament::button>
        </div>
    </form>

    <x-filament::section>
        <x-slot name="heading">Settings File</x-slot>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Settings are persisted to: <code class="rounded bg-gray-100 px-1 py-0.5 dark:bg-gray-800">{{ $this->getSettingsFilePath() }}</code>
        </p>
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
            This file is the single source of truth for <code>firewall.enable_log</code> and <code>firewall.log_stack</code>.
            Values are merged over the published config at boot time.
        </p>
    </x-filament::section>

    @php
        $snapshots = $this->getSnapshots();
    @endphp

    <x-filament::section>
        <x-slot name="heading">Rollback History</x-slot>
        <x-slot name="description">Previous settings snapshots. Restore any snapshot to revert settings.</x-slot>

        @if (count($snapshots) === 0)
            <p class="text-sm text-gray-500 dark:text-gray-400">No snapshots available yet. Snapshots are created automatically each time you save.</p>
        @else
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($snapshots as $snapshot)
                    <div class="flex items-center justify-between py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $snapshot['date'] }}</p>
                            <div class="mt-1 flex gap-3 text-xs text-gray-500 dark:text-gray-400">
                                @foreach ($snapshot['settings'] as $key => $value)
                                    <span>
                                        <code>{{ $key }}</code>:
                                        @if (is_bool($value))
                                            {{ $value ? 'true' : 'false' }}
                                        @else
                                            {{ $value ?? 'null' }}
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <x-filament::button
                            size="sm"
                            color="warning"
                            icon="heroicon-o-arrow-uturn-left"
                            wire:click="restoreSnapshot('{{ $snapshot['id'] }}')"
                            wire:confirm="Are you sure you want to restore settings from {{ $snapshot['date'] }}?"
                        >
                            Restore
                        </x-filament::button>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
