<x-filament-panels::page>
    @if ($this->isConfigMode())
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2 text-warning-600 dark:text-warning-400">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5" />
                    Config Mode Active
                </div>
            </x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Firewall is running in config mode. Changes made here affect the current process only and will not persist across requests. Enable <code>firewall.use_database</code> to persist changes.
            </p>
        </x-filament::section>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
