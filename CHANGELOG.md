# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Notes
- Release lane policy: `main` remains the Filament 4 line (`v0.2.0+`), while `filament/v3` remains the Filament 3 maintenance line (`v0.1.x`).
- `v0.1.x` patch/minor tags are cut from `filament/v3` only.
- Filament 3 backport planning now defines explicit v3 adaptation constraints for action namespaces, slug signatures, static property typing, and v0.1.x Composer guardrails.
- `v0.1.x` now has an executable compatibility baseline/matrix (PHP 8.1-8.3, Laravel 10-13, Filament 3) with mandatory domain pass criteria and a `filament/v3` release-gate workflow.

## [0.1.x-backport] - 2026-04-23

### Added
- Published fixed-location backport release guidance at `docs/releases/v0.1.x-backport.md`, including scope, exclusions, upgrade/backport steps, known limitations, and links to the decision table and CI matrix.
- Clarified README version-support mapping so `main` remains the Filament 4 release lane (`v0.2.0+`) while `filament/v3` remains the Filament 3 backport lane (`v0.1.x`).

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

[0.1.0]: https://github.com/magentron/laravel-firewall-filament/releases/tag/v0.1.0
