<?php

namespace Magentron\LaravelFirewallFilament\Adapters;

use Illuminate\Support\Collection;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;
use PragmaRX\Firewall\Vendor\Laravel\Models\Firewall as FirewallModel;

class DatabaseRuleStoreAdapter implements RuleStoreAdapter
{
    public function all(): Collection
    {
        $databaseIps = $this->getDatabaseIps();

        return Firewall::all()->map(function ($model) use ($databaseIps) {
            $ip = $model->ip_address;
            $source = isset($databaseIps[$ip]) ? 'database' : 'config';

            return new RuleEntry(
                ip_address: $ip,
                whitelisted: (bool) $model->whitelisted,
                source: $source,
            );
        });
    }

    public function find(string $ip): ?RuleEntry
    {
        $model = Firewall::find($ip);

        if ($model === null) {
            return null;
        }

        $databaseIps = $this->getDatabaseIps();
        $source = isset($databaseIps[$ip]) ? 'database' : 'config';

        return new RuleEntry(
            ip_address: $model->ip_address,
            whitelisted: (bool) $model->whitelisted,
            source: $source,
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
        if ($this->isConfigSourced($ip)) {
            return false;
        }

        return Firewall::remove($ip);
    }

    public function move(string $ip, bool $whitelisted): bool
    {
        if ($this->isConfigSourced($ip)) {
            return false;
        }

        $removed = Firewall::remove($ip);

        if (! $removed) {
            return false;
        }

        return $this->add($ip, $whitelisted, true);
    }

    private function isConfigSourced(string $ip): bool
    {
        $entry = $this->find($ip);

        return $entry !== null && $entry->source === 'config';
    }

    private function getDatabaseIps(): array
    {
        if (! config('firewall.use_database')) {
            return [];
        }

        try {
            return FirewallModel::query()->pluck('ip_address', 'ip_address')->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
