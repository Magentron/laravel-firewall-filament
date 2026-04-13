<?php

namespace Magentron\LaravelFirewallFilament\Tests\Support;

use Filament\Panel;
use Filament\PanelProvider;
use Magentron\LaravelFirewallFilament\FirewallFilamentPlugin;

/**
 * Minimal Filament panel used by feature tests that need to mount
 * Livewire components backed by Filament pages and resources.
 *
 * The panel reads the "test.firewall_filament.abilities" config key to
 * decide which abilities to grant for the current test, so individual
 * tests can flip authorization without constructing a new panel each time.
 */
class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('testing')
            ->path('testing')
            ->default()
            ->plugin(
                FirewallFilamentPlugin::make()
                    ->authorizeUsing(function ($user, string $ability): bool {
                        $granted = (array) config('test.firewall_filament.abilities', []);

                        return in_array($ability, $granted, true);
                    })
            );
    }
}
