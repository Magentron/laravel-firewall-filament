<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" icon="heroicon-o-check">
                {{ __('firewall-filament::firewall-filament.settings.action.save') }}
            </x-filament::button>
        </div>
    </form>

    <x-filament::section>
        <x-slot name="heading">{{ __('firewall-filament::firewall-filament.settings.section.file') }}</x-slot>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('firewall-filament::firewall-filament.settings.file.path') }} <code class="rounded bg-gray-100 px-1 py-0.5 dark:bg-gray-800">{{ $this->getSettingsFilePath() }}</code>
        </p>
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
            {!! __('firewall-filament::firewall-filament.settings.file.description') !!}
        </p>
    </x-filament::section>

    @php
        $snapshots = $this->getSnapshots();
    @endphp

    <x-filament::section>
        <x-slot name="heading">{{ __('firewall-filament::firewall-filament.settings.section.rollback') }}</x-slot>
        <x-slot name="description">{{ __('firewall-filament::firewall-filament.settings.section.rollback_description') }}</x-slot>

        @if (count($snapshots) === 0)
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('firewall-filament::firewall-filament.settings.snapshot.empty') }}</p>
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
                            wire:confirm="{{ __('firewall-filament::firewall-filament.settings.snapshot.confirm', ['date' => $snapshot['date']]) }}"
                        >
                            {{ __('firewall-filament::firewall-filament.settings.snapshot.restore') }}
                        </x-filament::button>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
