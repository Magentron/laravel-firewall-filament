<?php

namespace Magentron\LaravelFirewallFilament\Adapters;

use Illuminate\Support\Collection;

interface RuleStoreAdapter
{
    /**
     * @return Collection<int, RuleEntry>
     */
    public function all(): Collection;

    public function find(string $ip): ?RuleEntry;

    public function add(string $ip, bool $whitelisted, bool $force = false): bool;

    public function remove(string $ip): bool;

    public function move(string $ip, bool $whitelisted): bool;
}
