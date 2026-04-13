<?php

namespace Magentron\LaravelFirewallFilament\Adapters;

use Illuminate\Support\Collection;

class NullLogSourceAdapter implements LogSourceAdapter
{
    public function supported(): bool
    {
        return false;
    }

    public function recentEntries(int $limit): iterable
    {
        return new Collection();
    }
}
