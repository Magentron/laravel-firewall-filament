<?php

return [
    'log_file' => null,

    // Path to the JSON file used to persist runtime-writable settings
    // (firewall.enable_log, firewall.log_stack). This is the single source
    // of truth for those two keys — values here are merged over the published
    // firewall config at boot time.
    'settings_file' => storage_path('app/firewall-filament-settings.json'),

    // Directory for settings rollback snapshots (JSON files, up to 10 retained).
    'settings_snapshot_dir' => storage_path('app/firewall-filament-snapshots'),
];
