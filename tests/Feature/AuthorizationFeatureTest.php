<?php

namespace Magentron\LaravelFirewallFilament\Tests\Feature;

use Magentron\LaravelFirewallFilament\FirewallFilamentPlugin;
use Magentron\LaravelFirewallFilament\Tests\TestCase;

class AuthorizationFeatureTest extends TestCase
{
    public function test_deny_by_default_without_callback(): void
    {
        $plugin = FirewallFilamentPlugin::make();

        $user = new \stdClass();

        foreach (FirewallFilamentPlugin::ABILITIES as $ability) {
            $this->assertFalse(
                $plugin->canForUser($user, $ability),
                "Ability '{$ability}' should be denied by default"
            );
        }
    }

    public function test_deny_by_default_can_returns_false_without_auth(): void
    {
        $plugin = FirewallFilamentPlugin::make();

        foreach (FirewallFilamentPlugin::ABILITIES as $ability) {
            $this->assertFalse($plugin->can($ability));
        }
    }

    public function test_is_authorized_returns_false_by_default(): void
    {
        $plugin = FirewallFilamentPlugin::make();
        $this->assertFalse($plugin->isAuthorized());
    }

    public function test_custom_callback_grants_selective_abilities(): void
    {
        $plugin = FirewallFilamentPlugin::make();
        $plugin->authorizeUsing(function ($user, $ability) {
            return in_array($ability, ['viewRules', 'viewLogs']);
        });

        $user = new \stdClass();

        $this->assertTrue($plugin->canForUser($user, 'viewRules'));
        $this->assertTrue($plugin->canForUser($user, 'viewLogs'));
        $this->assertFalse($plugin->canForUser($user, 'mutateRules'));
        $this->assertFalse($plugin->canForUser($user, 'viewSettings'));
        $this->assertFalse($plugin->canForUser($user, 'mutateSettings'));
    }

    public function test_config_mode_mutations_disabled_by_default(): void
    {
        $plugin = FirewallFilamentPlugin::make();
        $this->assertFalse($plugin->allowsConfigModeMutations());
    }

    public function test_config_mode_mutations_can_be_enabled(): void
    {
        $plugin = FirewallFilamentPlugin::make()->allowConfigModeMutations();
        $this->assertTrue($plugin->allowsConfigModeMutations());
    }
}
