<?php

namespace Magentron\LaravelFirewallFilament\Adapters;

use Illuminate\Support\Collection;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;

class ConfigRuleStoreAdapter implements RuleStoreAdapter
{
    public const NOT_PERSISTED_WARNING = 'Firewall is running in config mode. Changes are NOT persisted beyond the current process.';

    public function all(): Collection
    {
        return Firewall::all()->map(function ($model) {
            return new RuleEntry(
                ip_address: $model->ip_address,
                whitelisted: (bool) $model->whitelisted,
                source: 'config',
            );
        });
    }

    public function find(string $ip): ?RuleEntry
    {
        $model = Firewall::find($ip);

        if ($model === null) {
            return null;
        }

        return new RuleEntry(
            ip_address: $model->ip_address,
            whitelisted: (bool) $model->whitelisted,
            source: 'config',
        );
    }

    public function add(string $ip, bool $whitelisted, bool $force = false): bool
    {
        return $whitelisted
            ? Firewall::whitelist($ip, $force)
            : Firewall::blacklist($ip, $force);
    }

    public function remove(string $ip): bool
    {
        return Firewall::remove($ip);
    }

    public function move(string $ip, bool $whitelisted): bool
    {
        $this->remove($ip);

        return $this->add($ip, $whitelisted, true);
    }

    public function warning(): string
    {
        return self::NOT_PERSISTED_WARNING;
    }
}
