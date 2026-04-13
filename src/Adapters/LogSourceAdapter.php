<?php

namespace Magentron\LaravelFirewallFilament\Adapters;

interface LogSourceAdapter
{
    public function supported(): bool;

    /**
     * @return iterable<int, string>
     */
    public function recentEntries(int $limit): iterable;
}
