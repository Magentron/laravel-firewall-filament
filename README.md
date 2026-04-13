# Laravel Firewall Filament

A [Filament](https://filamentphp.com/) admin panel integration for [magentron/laravel-firewall](https://github.com/magentron/laravel-firewall). Manage your firewall IP whitelist and blacklist rules directly from your Filament dashboard.

## Requirements

- PHP 8.1+
- Laravel 10, 11, 12, or 13
- Filament 3 or 4
- [magentron/laravel-firewall](https://github.com/magentron/laravel-firewall) 3.x

## Installation

Install the package via Composer:

```bash
composer require magentron/laravel-firewall-filament
```

The service provider is auto-discovered by Laravel. No manual registration is needed.

## Registration

Register the plugin in your Filament panel provider:

```php
use Magentron\LaravelFirewallFilament\FirewallFilamentPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FirewallFilamentPlugin::make());
}
```

## Configuration

The plugin exposes fluent configuration methods:

```php
FirewallFilamentPlugin::make()
    ->navigationGroup('Security')
    ->slug('firewall')
    ->authorizeUsing(fn () => auth()->user()?->is_admin)
    ->enableSettings()
    ->enableLogs()
    ->enableWidgets();
```

By default, all admin surfaces **deny access** unless you configure `authorizeUsing()`. This is a secure default — you must explicitly grant access.

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
