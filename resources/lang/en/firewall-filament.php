<?php

return [
    // Navigation & resource labels
    'rules.navigation_label' => 'Firewall Rules',
    'rules.model_label' => 'Firewall Rule',
    'rules.plural_model_label' => 'Firewall Rules',
    'rules.title' => 'Firewall Rules',

    'audit.navigation_label' => 'Audit Trail',
    'audit.model_label' => 'Audit Entry',
    'audit.plural_model_label' => 'Audit Trail',
    'audit.title' => 'Audit Trail',

    'status.navigation_label' => 'Firewall Status',
    'status.title' => 'Firewall Status & Stats',

    'settings.navigation_label' => 'Firewall Settings',
    'settings.title' => 'Firewall Settings',

    // Rules table columns
    'rules.column.ip_address' => 'IP / CIDR / Range / Pattern',
    'rules.column.list' => 'List',
    'rules.column.source' => 'Source',
    'rules.column.updated' => 'Updated',

    // Rules filter
    'rules.filter.list' => 'List',
    'rules.filter.whitelist' => 'Whitelist',
    'rules.filter.blacklist' => 'Blacklist',

    // Rules actions
    'rules.action.move' => 'Move to other list',
    'rules.action.move_heading' => 'Move to other list',
    'rules.action.delete' => 'Delete',
    'rules.action.bulk_delete' => 'Delete selected',
    'rules.action.clear_all' => 'Clear all rules',
    'rules.action.clear_all_heading' => 'Clear all firewall rules',
    'rules.action.clear_all_description' => 'This will permanently delete ALL firewall rules (whitelist and blacklist). This action cannot be undone.',
    'rules.action.clear_all_confirm' => 'Yes, clear all rules',

    // Rules create form
    'rules.action.new_rule' => 'New rule',
    'rules.field.ip_address' => 'IP Address / Pattern',
    'rules.field.ip_address_helper' => 'Accepts: single IP, CIDR (e.g. 10.0.0.0/24), range (e.g. 10.0.0.1-10.0.0.255), country:XX, or host:domain.com. File-path entries are NOT accepted via the UI for security reasons; use the config file instead.',
    'rules.field.ip_address_validation' => 'The :attribute must be a valid IP address, CIDR notation, IP range, country:XX code, or host:domain pattern.',
    'rules.field.whitelist' => 'Whitelist',
    'rules.field.whitelist_helper' => 'Enable to add to the whitelist, disable to add to the blacklist.',

    // Rules notifications
    'rules.notification.moved' => 'Rule moved',
    'rules.notification.deleted' => 'Rule deleted',
    'rules.notification.bulk_deleted' => 'Rules deleted',
    'rules.notification.created' => 'Rule created',
    'rules.notification.cleared' => 'All rules cleared',
    'rules.notification.lockout_prevented' => 'Lockout prevented',
    'rules.notification.lockout_body' => 'Cannot blacklist :ip — it matches your current session IP.',
    'rules.notification.move_failed' => 'Move failed',
    'rules.notification.move_failed_body' => 'Could not move :ip. Possible causes: (a) the entry is config-sourced and read-only; (b) the upstream store rejected the change; (c) the remove step succeeded but the subsequent re-add failed — in that case the entry may have been deleted from the original list and NOT re-added. Verify the rules list and re-create the entry if necessary.',
    'rules.notification.delete_failed' => 'Delete failed',
    'rules.notification.delete_failed_body' => 'Could not remove :ip. It may be a config-sourced entry (read-only) or the upstream store rejected the change.',
    'rules.notification.create_failed' => 'Create failed',
    'rules.notification.create_failed_body' => 'Could not add :ip. The upstream store rejected the change (the entry may already exist on the other list; try moving it instead).',

    // Rules config mode
    'rules.config_mode.title' => 'Config Mode Active',
    'rules.config_mode.description' => 'Firewall is running in config mode. Changes made here affect the current process only and will not persist across requests. Enable <code>firewall.use_database</code> to persist changes.',
    'rules.config_mode.tooltip' => 'Mutations are disabled in config mode. Enable firewall.use_database to persist changes.',
    'rules.config_sourced.tooltip' => 'This entry originates from a config-array rule (e.g. CIDR, country, host, or file) and cannot be modified through the UI. Edit config/firewall.php instead.',

    // Status page
    'status.section.statistics' => 'Firewall Statistics',
    'status.stat.whitelist_entries' => 'Whitelist Entries',
    'status.stat.blacklist_entries' => 'Blacklist Entries',
    'status.stat.total_entries' => 'Total Entries',
    'status.section.configuration' => 'Configuration',
    'status.config.storage_mode' => 'Storage Mode',
    'status.config.database' => 'Database',
    'status.config.config' => 'Config',
    'status.config.logging_enabled' => 'Logging Enabled',
    'status.config.yes' => 'Yes',
    'status.config.no' => 'No',
    'status.config.log_stack' => 'Log Stack',
    'status.section.report' => 'Firewall Report',
    'status.section.activity' => 'Recent Firewall Activity',
    'status.log.unsupported' => 'Structured access logs are not provided by magentron/laravel-firewall. To enable best-effort log viewing, configure a <code class="rounded bg-warning-100 px-1 py-0.5 dark:bg-warning-800">LaravelLogFileAdapter</code> pointing at the log file used by your firewall log channel.',
    'status.log.disclaimer' => 'Best-effort log viewer — entries are raw log lines filtered for <code>FIREWALL:</code> prefix. Timestamps are parsed where possible. No filtering by URL, method, status, or matched rule is available.',
    'status.log.column.timestamp' => 'Timestamp',
    'status.log.column.entry' => 'Log Entry',
    'status.log.empty_heading' => 'No log entries',
    'status.log.empty_description' => 'No recent firewall log entries found.',

    // Settings page
    'settings.section.logging' => 'Logging',
    'settings.section.logging_description' => 'Configure firewall logging behaviour. Only these two settings are editable at runtime.',
    'settings.field.enable_log' => 'Enable Firewall Logging',
    'settings.field.enable_log_helper' => 'Toggle firewall request logging on or off.',
    'settings.field.log_stack' => 'Log Stack / Channel',
    'settings.field.log_stack_helper' => 'The Laravel log channel to use for firewall entries (e.g. "stack", "single", "daily").',
    'settings.action.save' => 'Save Settings',
    'settings.notification.saved' => 'Settings saved',
    'settings.notification.restored' => 'Settings restored',
    'settings.notification.restored_body' => 'Restored snapshot from :snapshot.',
    'settings.notification.restore_failed' => 'Restore failed',
    'settings.notification.unauthorized' => 'You are not authorized to modify firewall settings.',
    'settings.view_only_notice' => 'You have read-only access to these settings. Contact an administrator to request the mutateSettings ability to make changes.',
    'settings.section.file' => 'Settings File',
    'settings.file.path' => 'Settings are persisted to:',
    'settings.file.description' => 'This file is the single source of truth for <code>firewall.enable_log</code> and <code>firewall.log_stack</code>. Values are merged over the published config at boot time.',
    'settings.section.rollback' => 'Rollback History',
    'settings.section.rollback_description' => 'Previous settings snapshots. Restore any snapshot to revert settings.',
    'settings.snapshot.empty' => 'No snapshots available yet. Snapshots are created automatically each time you save.',
    'settings.snapshot.restore' => 'Restore',
    'settings.snapshot.confirm' => 'Are you sure you want to restore settings from :date?',

    // Audit log columns
    'audit.column.date' => 'Date',
    'audit.column.user_id' => 'User ID',
    'audit.column.ability' => 'Ability',
    'audit.column.action' => 'Action',
    'audit.column.target' => 'Target',
    'audit.column.before' => 'Before',
    'audit.column.after' => 'After',

    // Audit log filter options
    'audit.filter.action.add' => 'Add',
    'audit.filter.action.remove' => 'Remove',
    'audit.filter.action.move' => 'Move',
    'audit.filter.action.clear' => 'Clear',
    'audit.filter.action.settings_change' => 'Settings Change',
    'audit.filter.action.settings_restore' => 'Settings Restore',
    'audit.filter.ability.mutate_rules' => 'Mutate Rules',
    'audit.filter.ability.mutate_settings' => 'Mutate Settings',

    // Widgets
    'widget.rule_counts.whitelist' => 'Whitelisted IPs',
    'widget.rule_counts.blacklist' => 'Blacklisted IPs',
    'widget.rule_counts.total' => 'Total Rules',
    'widget.rule_counts.storage' => 'Storage Mode',
    'widget.recent_log.heading' => 'Recent Firewall Log',
    'widget.recent_log.column.time' => 'Time',
    'widget.recent_log.column.entry' => 'Log Entry',
];
