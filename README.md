# Laravel Firewall Filament

A [Filament](https://filamentphp.com/) admin panel integration for [magentron/laravel-firewall](https://github.com/magentron/laravel-firewall). Manage your firewall IP whitelist and blacklist rules directly from your Filament dashboard.

## Requirements

- PHP 8.1+
- Laravel 10, 11, 12, or 13
- Filament 3 or 4
- [magentron/laravel-firewall](https://github.com/magentron/laravel-firewall) 3.x

### Tested combinations

| PHP | Laravel | Filament |
|-----|---------|----------|
| 8.2 | 10      | 3        |
| 8.2 | 11      | 3        |
| 8.3 | 11      | 3        |
| 8.3 | 12      | 3        |
| 8.3 | 12      | 4        |
| 8.3 | 13      | 4        |

Combinations outside this matrix (e.g. PHP 8.1 + Laravel 11, Laravel 10 + Filament 4) are **best effort** — they may work but are not tested in CI and not claimed as supported.

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

## License

MIT. See [LICENSE](LICENSE) for details.
