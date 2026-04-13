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

## License

MIT. See [LICENSE](LICENSE) for details.
