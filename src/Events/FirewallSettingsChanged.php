<?php

namespace Magentron\LaravelFirewallFilament\Events;

class FirewallSettingsChanged
{
    public function __construct(
        public readonly array $settings,
        public readonly array $previous,
    ) {}
}
