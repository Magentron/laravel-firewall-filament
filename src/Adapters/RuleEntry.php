<?php

namespace Magentron\LaravelFirewallFilament\Adapters;

class RuleEntry
{
    public function __construct(
        public readonly string $ip_address,
        public readonly bool $whitelisted,
        public readonly string $source,
    ) {
    }
}
