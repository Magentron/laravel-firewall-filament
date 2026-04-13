<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Firewall Statistics</x-slot>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Whitelist Entries</p>
                <p class="mt-1 text-2xl font-semibold text-success-600 dark:text-success-400">{{ $this->getWhitelistCount() }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Blacklist Entries</p>
                <p class="mt-1 text-2xl font-semibold text-danger-600 dark:text-danger-400">{{ $this->getBlacklistCount() }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Entries</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $this->getTotalCount() }}</p>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Configuration</x-slot>

        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Storage Mode</dt>
                <dd class="mt-1">
                    @if ($this->getUseDatabase())
                        <x-filament::badge color="info">Database</x-filament::badge>
                    @else
                        <x-filament::badge color="warning">Config</x-filament::badge>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Logging Enabled</dt>
                <dd class="mt-1">
                    @if ($this->getEnableLog())
                        <x-filament::badge color="success">Yes</x-filament::badge>
                    @else
                        <x-filament::badge color="gray">No</x-filament::badge>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Log Stack</dt>
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
            <x-slot name="heading">Firewall Report</x-slot>
            <pre class="overflow-x-auto rounded-lg bg-gray-50 p-4 text-sm text-gray-800 dark:bg-gray-800 dark:text-gray-200">{{ $report }}</pre>
        </x-filament::section>
    @endif

    <x-filament::section>
        <x-slot name="heading">Recent Firewall Activity</x-slot>

        @if (! $this->isLogSupported())
            <div class="rounded-lg border border-warning-300 bg-warning-50 p-4 dark:border-warning-600 dark:bg-warning-900/20">
                <p class="text-sm text-warning-800 dark:text-warning-200">
                    Structured access logs are not provided by magentron/laravel-firewall. To enable best-effort log viewing, configure a <code class="rounded bg-warning-100 px-1 py-0.5 dark:bg-warning-800">LaravelLogFileAdapter</code> pointing at the log file used by your firewall log channel.
                </p>
            </div>
        @else
            <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                Best-effort log viewer — entries are raw log lines filtered for <code>FIREWALL:</code> prefix. Timestamps are parsed where possible. No filtering by URL, method, status, or matched rule is available.
            </p>
            {{ $this->table }}
        @endif
    </x-filament::section>
</x-filament-panels::page>
