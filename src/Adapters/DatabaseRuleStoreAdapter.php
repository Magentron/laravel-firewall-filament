<?php

namespace Magentron\LaravelFirewallFilament\Adapters;

use Illuminate\Support\Collection;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;

class DatabaseRuleStoreAdapter implements RuleStoreAdapter
{
    public function all(): Collection
    {
        $configIps = $this->getConfigIps();

        return Firewall::all()->map(function ($model) use ($configIps) {
            $ip = $model->ip_address;
            $source = isset($configIps[$ip]) ? 'config' : 'database';

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

        $configIps = $this->getConfigIps();
        $source = isset($configIps[$ip]) ? 'config' : 'database';

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
        return Firewall::remove($ip);
    }

    public function move(string $ip, bool $whitelisted): bool
    {
        $this->remove($ip);

        return $this->add($ip, $whitelisted, true);
    }

    private function getConfigIps(): array
    {
        $whitelist = (array) config('firewall.whitelist', []);
        $blacklist = (array) config('firewall.blacklist', []);

        $configIps = [];

        foreach ($whitelist as $ip) {
            $configIps[$ip] = true;
        }

        foreach ($blacklist as $ip) {
            $configIps[$ip] = true;
        }

        return $configIps;
    }
}
