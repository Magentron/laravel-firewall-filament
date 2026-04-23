# Laravel Firewall Filament

A [Filament](https://filamentphp.com/) admin panel integration for [magentron/laravel-firewall](https://github.com/magentron/laravel-firewall). Manage your firewall IP whitelist and blacklist rules directly from your Filament dashboard.

## Requirements

- PHP 8.1+
- Laravel 10, 11, 12, or 13
- Filament 3 or 4
- [magentron/laravel-firewall](https://github.com/magentron/laravel-firewall) 3.x

## Version support

| Lane | Supported tags | Filament major | Policy |
|---|---|---|---|
| `main` | `v0.2.0+` | 4 | Mainline development and releases happen on `main` only. |
| `filament/v3` | `v0.1.x` | 3 | Backports are released from `filament/v3` only. |

Backport release guidance is maintained in `docs/releases/v0.1.x-backport.md`.

### Compatibility Baseline (`v0.1.x` / `filament/v3`)

The `v0.1.x` maintenance lane is locked to Filament 3 and has this minimum baseline:

- PHP `8.1`
- Laravel `10`
- Filament `3`

Supported majors for this lane are PHP `8.1-8.3`, Laravel `10-13`, and Filament `3.x`. Baseline changes must be decided before implementation work begins and then mirrored in the CI matrix.

### CI matrix (`v0.1.x`)

| PHP | Laravel | Filament | Testbench |
|-----|---------|----------|-----------|
| 8.1 | 10      | 3        | 8         |
| 8.2 | 11      | 3        | 9         |
| 8.3 | 12      | 3        | 10        |
| 8.3 | 13      | 3        | 10        |

These combinations are executed in `.github/workflows/tests.yml` for pushes and pull requests on both `main` and `filament/v3`.

### Mandatory test domains and pass criteria

The backport lane requires explicit coverage in these domains:

| Domain | Primary tests |
|---|---|
| Rule CRUD | `tests/Feature/RuleCrudTest.php` |
| Auth gates | `tests/Feature/AuthorizationFeatureTest.php`, `tests/Feature/LivewireMutationAuthTest.php`, `tests/Feature/SettingsMutationAuthTest.php` |
| Config mode vs database mode | `tests/Feature/ConfigModeTest.php`, `tests/DatabaseRuleStoreAdapterTest.php` |
| Log adapter allowlisting | `tests/LogSourceAdapterTest.php`, `tests/Feature/ServiceProviderTest.php` |
| Settings allowlist/path hardening | `tests/SettingsStoreTest.php`, `tests/Feature/SettingsMutationAuthTest.php` |
| Audit logging | `tests/Feature/AuditTrailTest.php`, `tests/AuditLoggerTest.php` |

Pass criteria for backport acceptance:

- All existing and new tests pass in every CI matrix job.
- Mandatory domains are not skipped (`phpunit` runs with `--fail-on-skipped --fail-on-incomplete`).
- A backport pull request targeting `filament/v3` is not releasable unless the full matrix is green (enforced by the `v0.1.x Release Gate` job).

## Branch and release lanes

This repository uses two release lanes to keep Filament 3 backports isolated from Filament 4 mainline work:

- `main` is the Filament 4 line and publishes `v0.2.0+` releases.
- `filament/v3` is the Filament 3 maintenance line and publishes `v0.1.x` releases.

Tagging strategy:

- Create `v0.1.x` patch/minor tags from `filament/v3` only.
- Do not create `v0.1.x` tags from `main`.
- Reserve `main` tags for the Filament 4 line (`v0.2.0+`).

### Filament 3 adaptation notes (`filament/v3` lane)

When applying `v0.2.0` changes to the `filament/v3` backport lane, keep these translation rules:

| Area | Adaptation rule | Target files |
|---|---|---|
| Action namespaces | Keep Filament 3 split action classes: header actions use `Filament\Actions\Action`, table row actions use `Filament\Tables\Actions\Action` (aliased as `TableAction`), and bulk actions use `Filament\Tables\Actions\BulkAction`. | `src/Resources/FirewallRuleResource/Pages/ManageFirewallRules.php`, `src/Pages/FirewallSettingsPage.php` |
| Slug signatures | Keep resource/page slugs on the Filament 3-compatible signature `getSlug(?Panel $panel = null): string`. | `src/Resources/FirewallRuleResource.php`, `src/Resources/AuditLogResource.php`, `src/Pages/FirewallStatusPage.php`, `src/Pages/FirewallSettingsPage.php` |
| Static property/type differences | Preserve Filament 3-compatible static declarations (for example `protected static ?string $model`, `protected static string|BackedEnum|null $navigationIcon`) instead of v4-specific alternatives. | `src/Resources/FirewallRuleResource.php`, `src/Resources/AuditLogResource.php`, `src/Pages/FirewallStatusPage.php`, `src/Pages/FirewallSettingsPage.php` |
| UI parity | Keep behaviour equivalent (same actions, visibility/disabled guards, notifications, and translations) and avoid redesign changes. | `src/Resources/**`, `src/Pages/**`, `resources/views/**`, `resources/lang/**` |

Backport lane dependency constraints (`filament/v3`) are:

- `php`: `^8.1`
- `magentron/laravel-firewall`: `^3.0`
- `illuminate/support`: `^10.0|^11.0|^12.0|^13.0`
- `filament/filament`: `^3.0` only (no `^4.0`)
- `orchestra/testbench` (`require-dev`): `^8.0|^9.0|^10.0`
- `phpunit/phpunit` (`require-dev`): `^10.0|^11.0`

## Installation

Install the package via Composer:

```bash
composer require magentron/laravel-firewall-filament
```

The service provider is auto-discovered by Laravel. No manual registration is needed.

## Plugin registration

Register the plugin in your Filament panel provider:

```php
use Magentron\LaravelFirewallFilament\FirewallFilamentPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(
            FirewallFilamentPlugin::make()
                ->authorizeUsing(fn (object $user, string $ability): bool => $user->is_admin)
        );
}
```

## Authorization setup (required)

By default, **all access is denied**. You must configure an authorization callback before any user can access the firewall panel. This is a deliberate secure default.

The callback receives the authenticated user and a string ability name. Return `true` to allow:

```php
FirewallFilamentPlugin::make()
    ->authorizeUsing(function (object $user, string $ability): bool {
        // Grant all abilities to admins
        return $user->hasRole('admin');
    });
```

### Abilities

| Ability          | Controls                                    |
|------------------|---------------------------------------------|
| `viewRules`      | View the firewall rules list                |
| `mutateRules`    | Create, move, and delete rules              |
| `viewLogs`       | View the firewall status / log page         |
| `viewSettings`   | View the settings page                      |
| `mutateSettings` | Change and restore settings                 |

You can grant abilities selectively:

```php
->authorizeUsing(function (object $user, string $ability): bool {
    if ($user->hasRole('admin')) {
        return true;
    }

    // Operators can view but not mutate
    if ($user->hasRole('operator')) {
        return in_array($ability, ['viewRules', 'viewLogs']);
    }

    return false;
})
```

### Gate shortcut

If you prefer a single Laravel Gate for all abilities:

```php
FirewallFilamentPlugin::make()
    ->authorizeWithGate('manage-firewall');
```

This checks `Gate::allows('manage-firewall')` for every ability.

## Database mode vs config mode

The underlying `magentron/laravel-firewall` package supports two storage modes:

- **Database mode** (`firewall.use_database = true`): Rules are persisted in the database. Creating, moving, and deleting rules via the Filament panel works fully.
- **Config mode** (`firewall.use_database = false`): Rules are read from `config/firewall.php` arrays. The panel shows rules as read-only. Mutation actions are visible but disabled with a tooltip explaining why.

**If you want to manage rules from the admin panel, set `use_database` to `true`** in your `config/firewall.php`:

```php
// config/firewall.php
'use_database' => true,
```

Without this, the panel is view-only for rules. You can override this behaviour with `->allowConfigModeMutations()` on the plugin, but changes made in config mode will not persist across requests.

## Firewall entry validation contract

Firewall rule entry values accepted by the UI/API validator are:

- IPv4 (example: `192.168.1.10`)
- IPv6 (example: `2001:db8::1`)
- IPv4 CIDR (example: `10.0.0.0/24`)
- IPv6 CIDR (example: `2001:db8::/32`)
- IPv4 range (example: `10.0.0.1-10.0.0.255`)
- Country code selector (example: `country:US`)
- Host pattern selector (example: `host:example.com`)

Rejected patterns include:

- Empty/whitespace-only values
- Path-like inputs (for example `/etc/passwd`, `var/www/file.txt`)
- Windows path forms (for example `C:\\Windows\\System32`)
- Traversal-like values (for example `../secret`, `..\\secret`)

## Settings store

When `->enableSettings()` is active, the plugin provides a UI to edit `firewall.enable_log` and `firewall.log_stack` at runtime. These values are stored in a JSON file (default: `storage/app/firewall-filament-settings.json`) and merged over the firewall config at boot.

**Caveats:**

- The settings file must be writable by the web server. Ensure the `storage/app` directory has appropriate permissions.
- Settings are merged at boot time via `config()->set()`. If you use `config:cache`, the JSON file values will still take precedence because they are applied after the cached config is loaded.
- Up to 10 rollback snapshots are retained in `storage/app/firewall-filament-snapshots/`. Each save creates a snapshot of the previous state.
- Only `firewall.enable_log` and `firewall.log_stack` are writable. Other firewall config keys cannot be changed via the UI.

## Anti-lockout protection

When adding or moving a rule to the blacklist, the plugin detects whether the target IP/CIDR/range would block the current admin's IP address. If it would, the operation is prevented with a warning notification.

**This check covers exact IPs, CIDR notation, and IP ranges.** It does not cover country codes or hostnames since those cannot be reliably resolved to IP addresses at check time. Exercise caution when blacklisting broad patterns.

## Audit trail

Every mutation (rule create, move, delete, clear, settings change, settings restore) is recorded in the `firewall_filament_audit` database table. This table is owned by the package and is always available — it does not depend on `firewall.use_database`.

View the audit log from the "Audit Logs" resource in the Filament panel (requires `viewSettings` ability).

## Optional features

These features are disabled by default and can be enabled on the plugin:

```php
FirewallFilamentPlugin::make()
    ->authorizeUsing(fn ($user, $ability) => $user->is_admin)
    ->enableSettings()          // Settings page (enable_log, log_stack)
    ->enableLogs()              // Firewall status / log viewer page
    ->enableWidgets()           // Dashboard widgets (rule counts, recent log lines)
    ->navigationGroup('Security');
```

### Widgets

When `->enableWidgets()` is active, two dashboard widgets are registered:

- **Rule Counts** — stat cards showing whitelist count, blacklist count, total rules, and storage mode
- **Recent Log Lines** — table of the last 10 firewall log entries (only shown when a log source is configured)

Individual widgets can be toggled:

```php
FirewallFilamentPlugin::make()
    ->enableWidgets()
    ->enableRuleCountsWidget(true)
    ->enableRecentLogLinesWidget(false);
```

### Log viewer (best-effort)

`magentron/laravel-firewall` does **not** persist structured access logs. This package can read recent `FIREWALL:` log lines from a Laravel log file when configured with both a target path and an allowlist:

```php
// config/firewall-filament.php
return [
    'log_file' => storage_path('logs/laravel.log'),
    'log_file_allowlist' => [
        storage_path('logs/laravel.log'),
    ],
];
```

Behaviour:

- `log_file` must resolve (via `realpath()`) to one of the absolute entries in `log_file_allowlist`.
- If the allowlist is empty or no entry matches, log reading is disabled and the plugin falls back to `NullLogSourceAdapter`.
- The adapter scans only the tail of the file (256 KiB max), reads in binary mode, and truncates each line to 4 KiB.

## Publishing

The package provides several publishable assets. Use `vendor:publish` with the appropriate tag:

### Config

Publish the configuration file to `config/firewall-filament.php`:

```bash
php artisan vendor:publish --tag=firewall-filament-config
```

This allows you to customize the log file path, settings file location, and snapshot directory.

### Translations

Publish translation files to `lang/vendor/firewall-filament/`:

```bash
php artisan vendor:publish --tag=firewall-filament-translations
```

English translations are shipped by default. After publishing, you can customize all labels, navigation titles, notifications, form fields, and other UI strings. To add a new language, create a subdirectory (e.g. `lang/vendor/firewall-filament/nl/`) with a `firewall-filament.php` translation file.

### Views

Publish Blade views to `resources/views/vendor/firewall-filament/`:

```bash
php artisan vendor:publish --tag=firewall-filament-views
```

This allows you to customize the layout and markup of the firewall management pages (rules, status, and settings).

### Migrations

Publish database migrations to `database/migrations/`:

```bash
php artisan vendor:publish --tag=firewall-filament-migrations
```

This is useful if you need to customize the audit log table schema. Migrations are auto-loaded by the package, so publishing is optional.

## Backport Decision Table (v0.1.0..v0.2.0)

Scope discovery reviewed the mandatory coverage set for `v0.1.0..v0.2.0`: `src/**`, `database/**`, `resources/**`, `config/**`, `routes/**`, `tests/**`, `composer.json`, `composer.lock`, `.github/workflows/**`, `README.md`, `CHANGELOG.md`, and release-note artefacts (`CHANGELOG.md` + release commit metadata).

- Paths with changes in this range: `src/**`, `resources/**`, `config/**`, `tests/**`, `composer.json`, `.github/workflows/**`, `README.md`, `CHANGELOG.md`.
- Paths with no changes in this range: `database/**`, `routes/**`, `composer.lock`.

| Commit | Feature/fix summary | Target files | Risk | Decision | Rationale | Adaptation notes (required for `Adapt for v3`) |
|---|---|---|---|---|---|---|
| `057bee3624644a01eb6f612088280ec2168aee67` | Authorization hardening, DB-source correctness, log file allowlist, compatibility scope reset | `.github/workflows/tests.yml`, `CHANGELOG.md`, `README.md`, `composer.json`, `config/firewall-filament.php`, `resources/lang/en/firewall-filament.php`, `resources/views/pages/firewall-settings.blade.php`, `src/Adapters/DatabaseRuleStoreAdapter.php`, `src/Adapters/LaravelLogFileAdapter.php`, `src/FirewallFilamentPlugin.php`, `src/FirewallFilamentServiceProvider.php`, `src/Pages/FirewallSettingsPage.php`, `src/Pages/FirewallStatusPage.php`, `src/Resources/AuditLogResource.php`, `src/Resources/FirewallRuleResource.php`, `src/Resources/FirewallRuleResource/Pages/ManageFirewallRules.php`, `tests/Feature/LivewireCreateAuthTest.php`, `tests/Feature/PluginRegistrationTest.php`, `tests/Feature/RuleCrudTest.php`, `tests/LogSourceAdapterTest.php`, `tests/Support/TestPanelProvider.php` | high | Adapt for v3 | Security and correctness fixes are required for stable v0.1.x, but this commit mixes in Filament 4/Laravel 11+ signature and support-window changes. | Apply security/correctness changes to `src/Resources/FirewallRuleResource/Pages/ManageFirewallRules.php`, `src/Pages/FirewallSettingsPage.php`, `src/Adapters/DatabaseRuleStoreAdapter.php`, `src/Adapters/LaravelLogFileAdapter.php`, and matching tests; do **not** copy Filament 4-only API migrations in `src/FirewallFilamentPlugin.php`, `src/Resources/AuditLogResource.php`, `src/Resources/FirewallRuleResource.php`, or support-floor updates in `composer.json`/`.github/workflows/tests.yml`. |
| `b72c24f135a22dd021a78a54ed1c15b5c2a696ea` | Live authorization re-check, memoised DB reads, refactor cleanup, log misconfiguration warning | `config/firewall-filament.php`, `resources/lang/en/firewall-filament.php`, `src/Adapters/ConfigRuleStoreAdapter.php`, `src/Adapters/DatabaseRuleStoreAdapter.php`, `src/Adapters/RuleStoreAdapter.php`, `src/FirewallFilamentServiceProvider.php`, `src/Pages/FirewallSettingsPage.php`, `src/Pages/FirewallStatusPage.php`, `src/Resources/FirewallRuleResource/Pages/ManageFirewallRules.php`, `tests/Feature/LivewireCreateAuthTest.php`, `tests/Feature/LivewireMutationAuthTest.php` | medium | Adapt for v3 | Runtime hardening/performance changes are valuable for backport; touched code is mostly portable but contains Filament 4 naming/signature assumptions. | Port helper-level logic and guard flow in `src/Resources/FirewallRuleResource/Pages/ManageFirewallRules.php`, adapter memoisation/interface updates in `src/Adapters/DatabaseRuleStoreAdapter.php` + `src/Adapters/RuleStoreAdapter.php` + `src/Adapters/ConfigRuleStoreAdapter.php`, and warning path in `src/FirewallFilamentServiceProvider.php`; adjust action class/import usage and any method signatures to Filament 3 equivalents during port. |
| `e792e8e0c54246c8c5cbe69289b88b77799b4962` | v0.2.0 release-note cut | `CHANGELOG.md` | low | Exclude | Release-tag/changelog cut for v0.2.0 is historical metadata and should not be replayed into v0.1.x backport commits. | n/a |
| `fc8557910dbb2ad7c85d610e85c7448a3344b22b` | Docs branch-language cleanup referencing v0.2.0/v0.1.0 tags | `CHANGELOG.md`, `README.md` | low | Backport | Documentation clarification is version-management guidance and remains valid when maintaining a backport branch. | n/a |

Explicit exclusion: Filament UI redesign work and Filament 4-only UI/API migration changes are out of scope for backport into the Filament 3 maintenance line.

## License

MIT. See [LICENSE](LICENSE) for details.
