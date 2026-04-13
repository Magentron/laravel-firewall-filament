<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">{{ __('firewall-filament::firewall-filament.status.section.statistics') }}</x-slot>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('firewall-filament::firewall-filament.status.stat.whitelist_entries') }}</p>
                <p class="mt-1 text-2xl font-semibold text-success-600 dark:text-success-400">{{ $this->getWhitelistCount() }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('firewall-filament::firewall-filament.status.stat.blacklist_entries') }}</p>
                <p class="mt-1 text-2xl font-semibold text-danger-600 dark:text-danger-400">{{ $this->getBlacklistCount() }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('firewall-filament::firewall-filament.status.stat.total_entries') }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $this->getTotalCount() }}</p>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">{{ __('firewall-filament::firewall-filament.status.section.configuration') }}</x-slot>

        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('firewall-filament::firewall-filament.status.config.storage_mode') }}</dt>
                <dd class="mt-1">
                    @if ($this->getUseDatabase())
                        <x-filament::badge color="info">{{ __('firewall-filament::firewall-filament.status.config.database') }}</x-filament::badge>
                    @else
                        <x-filament::badge color="warning">{{ __('firewall-filament::firewall-filament.status.config.config') }}</x-filament::badge>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('firewall-filament::firewall-filament.status.config.logging_enabled') }}</dt>
                <dd class="mt-1">
                    @if ($this->getEnableLog())
                        <x-filament::badge color="success">{{ __('firewall-filament::firewall-filament.status.config.yes') }}</x-filament::badge>
                    @else
                        <x-filament::badge color="gray">{{ __('firewall-filament::firewall-filament.status.config.no') }}</x-filament::badge>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('firewall-filament::firewall-filament.status.config.log_stack') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ $this->getLogStack() ?? '—' }}
                </dd>
            </div>
        </dl>
    </x-filament::section>

    @php
        $report = $this->getFirewallReport();
    @endphp

    @if ($report !== null)
        <x-filament::section>
            <x-slot name="heading">{{ __('firewall-filament::firewall-filament.status.section.report') }}</x-slot>
            <pre class="overflow-x-auto rounded-lg bg-gray-50 p-4 text-sm text-gray-800 dark:bg-gray-800 dark:text-gray-200">{{ $report }}</pre>
        </x-filament::section>
    @endif

    <x-filament::section>
        <x-slot name="heading">{{ __('firewall-filament::firewall-filament.status.section.activity') }}</x-slot>

        @if (! $this->isLogSupported())
            <div class="rounded-lg border border-warning-300 bg-warning-50 p-4 dark:border-warning-600 dark:bg-warning-900/20">
                <p class="text-sm text-warning-800 dark:text-warning-200">
                    {!! __('firewall-filament::firewall-filament.status.log.unsupported') !!}
                </p>
            </div>
        @else
            <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                {!! __('firewall-filament::firewall-filament.status.log.disclaimer') !!}
            </p>
            {{ $this->table }}
        @endif
    </x-filament::section>
</x-filament-panels::page>
