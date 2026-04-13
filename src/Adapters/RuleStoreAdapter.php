<?php

namespace Magentron\LaravelFirewallFilament\Adapters;

use Illuminate\Support\Collection;

/**
 * Contract for reading and mutating firewall rules through an adapter.
 *
 * Implementations wrap the upstream `magentron/laravel-firewall` facade
 * and are responsible for tagging each returned entry with its origin
 * (`database` vs `config`) so callers can decide whether mutation is
 * safe.
 */
interface RuleStoreAdapter
{
    /**
     * Return every rule currently visible to the upstream store.
     *
     * @return Collection<int, RuleEntry>
     */
    public function all(): Collection;

    /**
     * Look up a single rule by IP and return it as a `RuleEntry`, or null
     * if upstream does not know it.
     */
    public function find(string $ip): ?RuleEntry;

    /**
     * Add an IP to the whitelist (if `$whitelisted`) or blacklist.
     * Returns the upstream store's success flag.
     */
    public function add(string $ip, bool $whitelisted, bool $force = false): bool;

    /**
     * Remove an IP from the upstream store. Callers that already hold a
     * resolved `RuleEntry` should pass it as `$knownEntry` to avoid a
     * redundant `find()` round-trip and to let the adapter skip
     * config-sourced entries without re-resolving their origin.
     */
    public function remove(string $ip, ?RuleEntry $knownEntry = null): bool;

    /**
     * Move an IP between whitelist and blacklist. Implemented as
     * remove + re-add where upstream lacks an in-place update.
     */
    public function move(string $ip, bool $whitelisted, ?RuleEntry $knownEntry = null): bool;
}
