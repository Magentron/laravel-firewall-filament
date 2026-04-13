<?php

return [
    // Path to the log file to read for best-effort firewall log line display.
    // Must also appear in `log_file_allowlist` below; if it does not, the
    // LaravelLogFileAdapter refuses to open it and falls back to NullLogSourceAdapter.
    'log_file' => null,

    // Explicit allowlist of absolute log file paths the LaravelLogFileAdapter
    // may open. Paths are canonicalized via realpath() before comparison, so
    // symlinks resolving outside this list are rejected. An empty list disables
    // log file reading entirely, regardless of `log_file`.
    //
    // Note on log rotation: paths are matched by strict realpath() equality.
    // An entry like `/var/log/laravel.log` will NOT match rotated variants
    // such as `/var/log/laravel.log.1` or `/var/log/laravel.log-2026-04-13`.
    // If you rotate logs, either point `log_file` at a symlink that always
    // resolves to the current active log and add the symlink target to the
    // allowlist, or list every rotated name you want to permit.
    'log_file_allowlist' => [],

    // Path to the JSON file used to persist runtime-writable settings
    // (firewall.enable_log, firewall.log_stack). This is the single source
    // of truth for those two keys — values here are merged over the published
    // firewall config at boot time.
    'settings_file' => storage_path('app/firewall-filament-settings.json'),

    // Directory for settings rollback snapshots (JSON files, up to 10 retained).
    'settings_snapshot_dir' => storage_path('app/firewall-filament-snapshots'),
];
