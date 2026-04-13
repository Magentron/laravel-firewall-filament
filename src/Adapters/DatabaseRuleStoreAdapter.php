<?php

namespace Magentron\LaravelFirewallFilament\Adapters;

use Illuminate\Support\Collection;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;
use PragmaRX\Firewall\Vendor\Laravel\Models\Firewall as FirewallModel;
use Throwable;

/**
 * Rule store adapter for database-backed firewall rules.
 *
 * Delegates all mutations through the upstream Firewall facade while
 * classifying each returned entry as either `database` (persisted in the
 * `firewall` table) or `config` (expanded from `firewall.whitelist` /
 * `firewall.blacklist` config arrays). Config-sourced entries are refused
 * by `remove()` and `move()` because upstream's delete flow cannot mutate
 * them cleanly.
 *
 * Side-effects:
 * - `getDatabaseIps()` queries the `firewall` table once per adapter
 *   instance and memoises the result; on query failure the method logs
 *   via `report()` and fails closed (treats all entries as config-sourced).
 */
class DatabaseRuleStoreAdapter implements RuleStoreAdapter
{
    /**
     * Memoised set of IPs persisted in the `firewall` DB table, keyed by
     * `ip_address`. Null until `getDatabaseIps()` has run once.
     *
     * @var array<string, string>|null
     */
    protected ?array $databaseIps = null;

    /**
     * Return every rule currently visible to the upstream Firewall facade,
     * tagged with its source (`database` vs `config`).
     */
    public function all(): Collection
    {
        $databaseIps = $this->getDatabaseIps();

        return Firewall::all()->map(function ($model) use ($databaseIps) {
            $ip     = $model->ip_address;
            $source = isset($databaseIps[$ip]) ? 'database' : 'config';

            return new RuleEntry(
                ip_address:  $ip,
                whitelisted: (bool) $model->whitelisted,
                source:      $source,
            );
        });
    }

    /**
     * Look up a single rule by IP and return it as a `RuleEntry`, or null
     * if upstream does not know it.
     */
    public function find(string $ip): ?RuleEntry
    {
        $model = Firewall::find($ip);

        if (null === $model) {
            return null;
        }

        $databaseIps = $this->getDatabaseIps();
        $source      = isset($databaseIps[$ip]) ? 'database' : 'config';

        return new RuleEntry(
            ip_address:  $model->ip_address,
            whitelisted: (bool) $model->whitelisted,
            source:      $source,
        );
    }

    /**
     * Add an IP to the whitelist or blacklist via the upstream facade.
     */
    public function add(string $ip, bool $whitelisted, bool $force = false): bool
    {
        return $whitelisted
            ? Firewall::whitelist($ip, $force)
            : Firewall::blacklist($ip, $force);
    }

    /**
     * Remove an IP from the upstream store. Config-sourced entries are
     * refused (they cannot be mutated cleanly). Callers that already hold
     * a resolved `RuleEntry` should pass it as `$knownEntry` to avoid a
     * second `Firewall::find()` round-trip.
     */
    public function remove(string $ip, ?RuleEntry $knownEntry = null): bool
    {
        if ($this->isConfigSourced($ip, $knownEntry)) {
            return false;
        }

        return Firewall::remove($ip);
    }

    /**
     * Move an IP between whitelist and blacklist. Upstream has no in-place
     * update, so this is implemented as remove + re-add with `$force=true`.
     *
     * Side-effect warning: if `remove()` succeeds but the subsequent `add()`
     * fails, the entry is gone from the store and this method returns false
     * WITHOUT rolling back. Callers MUST surface this clearly — the UI
     * notification should tell the operator the entry may need to be
     * manually re-created.
     */
    public function move(string $ip, bool $whitelisted, ?RuleEntry $knownEntry = null): bool
    {
        if ($this->isConfigSourced($ip, $knownEntry)) {
            return false;
        }

        $removed = Firewall::remove($ip);

        if (! $removed) {
            return false;
        }

        return $this->add($ip, $whitelisted, true);
    }

    /**
     * Check whether a given IP is config-sourced and therefore read-only.
     * Accepts a pre-resolved `RuleEntry` from the caller to avoid an extra
     * `Firewall::find()` + `getDatabaseIps()` round-trip.
     */
    protected function isConfigSourced(string $ip, ?RuleEntry $knownEntry = null): bool
    {
        if (null !== $knownEntry) {
            return 'config' === $knownEntry->source;
        }

        $entry = $this->find($ip);

        return null !== $entry && 'config' === $entry->source;
    }

    /**
     * Return a set (keyed by `ip_address`) of IPs that are persisted in the
     * `firewall` database table. Any IP returned by `Firewall::all()` that
     * is NOT in this set was expanded from config arrays (CIDR, country:,
     * host:, file:...) by upstream's `IpList::getNonDatabaseIps()` and
     * must be treated as config-sourced and read-only, because
     * `Firewall::remove()` cannot mutate config-derived entries.
     *
     * Side-effects:
     * - Result is memoised on the adapter instance — safe for bulk-delete
     *   / clear-all loops that would otherwise issue one full table pluck
     *   per record.
     * - On query failure (e.g. DB outage, migration not run), fails closed
     *   by returning `[]` (every entry classifies as `config`, blocking
     *   mutations) and reports the exception via `report()` so operators
     *   see a breadcrumb instead of a silent UI downgrade.
     */
    protected function getDatabaseIps(): array
    {
        if (null !== $this->databaseIps) {
            return $this->databaseIps;
        }

        if (! config('firewall.use_database')) {
            return $this->databaseIps = [];
        }

        try {
            return $this->databaseIps = FirewallModel::query()
                ->pluck('ip_address', 'ip_address')
                ->all();
        } catch (Throwable $e) {
            // Fail closed: every entry will classify as config-sourced
            // and mutations will be blocked. Report so ops know why.
            report($e);

            return $this->databaseIps = [];
        }
    }
}
