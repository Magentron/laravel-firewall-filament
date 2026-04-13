# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- **BREAKING**: Dropped Filament 3 support. The `main` / `2.x` branch now targets Filament 4 only. Rationale and full divergence list in README "Filament version support" section and PRD §7.3. A `1.x` branch for Filament 3 is not cut; open an issue if you need it.
- **BREAKING**: Dropped Laravel 10 support as a consequence of the Filament 4 requirement. Filament 4's `filament/support` requires `illuminate/contracts: ^11.28|^12.0|^13.0`, which excludes Laravel 10 at composer resolution time. Supported Laravel versions are now **11.28+, 12, and 13**.
- **BREAKING**: Dropped PHP 8.1 support. Filament 4 requires PHP ^8.2. Minimum PHP is now **8.2**.
- `composer.json` `filament/filament` constraint tightened from `^3.0|^4.0` to `^4.0`.
- `composer.json` `illuminate/support` constraint tightened from `^10.0|^11.0|^12.0|^13.0` to `^11.28|^12.0|^13.0`.
- `composer.json` `php` constraint tightened from `^8.1` to `^8.2` to match Filament 4's minimum.
- `composer.json` `orchestra/testbench` dev constraint tightened from `^8.0|^9.0|^10.0` to `^9.0|^10.0` (testbench 8 was for Laravel 10, now unreachable).
- `.github/workflows/tests.yml` matrix updated to match the new support contract: 4 jobs (PHP 8.2/L11, PHP 8.3/L11, PHP 8.3/L12, PHP 8.3/L13), all on Filament 4. The previous 6-job matrix (4× Filament 3 + 2× Filament 4, including an impossible L10+F4 entry) is removed.
- Updated core class declarations to Filament 4 signatures:
  - `$navigationIcon` on resources and pages: `?string` → `string | BackedEnum | null`.
  - `Resource::getSlug()`: now accepts `?Panel $panel = null` per v4 parent signature.
  - `Page::$view`: `protected static string` → `protected string` (v4 made this instance-level).
  - Table actions imports: `Filament\Tables\Actions\Action` / `BulkAction` → `Filament\Actions\Action` / `BulkAction`.

### Added
- Feature-level regression test (`tests/Feature/LivewireCreateAuthTest.php`) exercising the server-side `mutateRules` guard on the create action through the action-invocation path, not just UI visibility. Catches future regressions where `abort(403)` is removed but `->visible()` remains.
- Minimal test panel provider (`tests/Support/TestPanelProvider.php`) for feature tests that need a live Filament panel context.
- Translation keys: `rules.notification.create_failed`, `rules.notification.create_failed_body`.

### Security
- Create action now has a server-side `abort(403)` guard in addition to `->visible()`, matching the pattern already applied to move/delete/bulk-delete/clearAll.
- Create action now checks the adapter's return value and skips the audit log + success notification on failure (no more false success claims).
- `FirewallSettingsPage::save()` and `restoreSnapshot()` now `abort(403)` when the current user lacks `mutateSettings`, closing a Livewire-level bypass where `viewSettings`-only users could submit the form directly.
- Blade view `firewall-settings.blade.php` now gates the form and snapshot restore buttons on `canMutateSettings()`.
- `FirewallFilamentPlugin::register()` now only adds `FirewallStatusPage` to the panel when `enableLogs()` is set; previously the toggle was effectively ignored for status page registration.
- `FirewallStatusPage::canAccess()` now checks both `$plugin->hasLogs()` and `can('viewLogs')`.
- `DatabaseRuleStoreAdapter` source detection rewritten to query the upstream `firewall` table directly instead of string-comparing against raw config arrays. Previously, config-expanded entries (CIDR, `country:`, `host:`, file) could be misclassified as database-sourced and re-enable unsafe mutations.
- `DatabaseRuleStoreAdapter::remove()` and `move()` now refuse to act on config-sourced entries; the UI shows per-row disabled state and a `rules.config_sourced.tooltip` explaining why.
- Rule resource row key is now composite (`source:ip_address`) to avoid collisions when the same IP appears from both sources.
- Bulk delete and clear-all actions now check the adapter's return value and only audit entries that were actually removed; skipped counts are surfaced in the notification.
- `LaravelLogFileAdapter` now requires an explicit `log_file_allowlist` entry. The path and each allowlist entry are canonicalized via `realpath()` before comparison, defeating symlink traversal. Empty allowlist = disabled, even if `log_file` is set.

## [0.1.0] - 2026-04-13

### Added
- Filament plugin with auto-discovery service provider
- Firewall rules management (list, create, move between whitelist/blacklist, delete, bulk delete, clear all)
- Database and config storage mode support with read-only UI for config mode
- Firewall status page with statistics, configuration state, and log viewer
- Dashboard widgets: rule counts and recent log lines
- Settings page for runtime `firewall.enable_log` and `firewall.log_stack` configuration
- File-based settings store with snapshot rollback (up to 10 snapshots)
- Granular ability-based authorization (`viewRules`, `mutateRules`, `viewLogs`, `viewSettings`, `mutateSettings`)
- Secure default: all access denied until `authorizeUsing()` is configured
- `authorizeWithGate()` shortcut for single Laravel Gate authorization
- Anti-lockout detection preventing admins from blacklisting their own IP
- Audit trail logging all mutations to a package-owned database table
- IP/CIDR/range/country/hostname validation with path traversal protection
- Publishable config, translations, views, and migrations
- English translations for all UI strings
- 166 automated tests (121 unit + 45 feature)
- CI matrix: PHP 8.2–8.3, Laravel 10–13, Filament 3–4

> **Note (historical):** The Filament 3 / Laravel 10 / PHP 8.1 support claimed in this 0.1.0 entry has been **superseded** by the breaking changes in the `[Unreleased]` section above. On the `main` / `2.x` branch the supported matrix is now PHP 8.2+, Laravel 11.28+/12/13, Filament 4 only. This 0.1.0 entry is retained as-is for historical accuracy; do not rely on it as the current support contract.

[0.1.0]: https://github.com/magentron/laravel-firewall-filament/releases/tag/v0.1.0
