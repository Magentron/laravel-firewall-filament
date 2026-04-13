<?php

namespace Magentron\LaravelFirewallFilament\Tests;

use Magentron\LaravelFirewallFilament\FirewallFilamentPlugin;
use PHPUnit\Framework\TestCase;

class AuthorizationTest extends TestCase
{
    private FirewallFilamentPlugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plugin = FirewallFilamentPlugin::make();
    }

    public function test_abilities_constant_contains_all_expected_abilities(): void
    {
        $this->assertSame([
            'viewRules',
            'mutateRules',
            'viewLogs',
            'viewSettings',
            'mutateSettings',
        ], FirewallFilamentPlugin::ABILITIES);
    }

    public function test_default_denies_all_abilities(): void
    {
        foreach (FirewallFilamentPlugin::ABILITIES as $ability) {
            $this->assertFalse($this->plugin->can($ability), "Ability '{$ability}' should be denied by default");
        }
    }

    public function test_is_authorized_returns_false_by_default(): void
    {
        $this->assertFalse($this->plugin->isAuthorized());
    }

    public function test_authorize_using_is_fluent(): void
    {
        $result = $this->plugin->authorizeUsing(fn ($user, $ability) => true);
        $this->assertSame($this->plugin, $result);
    }

    public function test_authorize_using_receives_user_and_ability(): void
    {
        $receivedUser = null;
        $receivedAbility = null;

        $this->plugin->authorizeUsing(function ($user, $ability) use (&$receivedUser, &$receivedAbility) {
            $receivedUser = $user;
            $receivedAbility = $ability;

            return true;
        });

        $fakeUser = new \stdClass();
        $result = $this->plugin->canForUser($fakeUser, 'viewRules');

        $this->assertTrue($result);
        $this->assertSame($fakeUser, $receivedUser);
        $this->assertSame('viewRules', $receivedAbility);
    }

    public function test_authorize_using_callback_can_grant_specific_abilities(): void
    {
        $this->plugin->authorizeUsing(function ($user, $ability) {
            return $ability === 'viewRules';
        });

        $fakeUser = new \stdClass();
        $this->assertTrue($this->plugin->canForUser($fakeUser, 'viewRules'));
        $this->assertFalse($this->plugin->canForUser($fakeUser, 'mutateRules'));
        $this->assertFalse($this->plugin->canForUser($fakeUser, 'viewLogs'));
        $this->assertFalse($this->plugin->canForUser($fakeUser, 'viewSettings'));
        $this->assertFalse($this->plugin->canForUser($fakeUser, 'mutateSettings'));
    }

    public function test_authorize_using_callback_can_grant_all_abilities(): void
    {
        $this->plugin->authorizeUsing(fn ($user, $ability) => true);

        $fakeUser = new \stdClass();
        foreach (FirewallFilamentPlugin::ABILITIES as $ability) {
            $this->assertTrue($this->plugin->canForUser($fakeUser, $ability), "Ability '{$ability}' should be granted");
        }
    }

    public function test_authorize_using_callback_can_deny_all_abilities(): void
    {
        $this->plugin->authorizeUsing(fn ($user, $ability) => false);

        $fakeUser = new \stdClass();
        foreach (FirewallFilamentPlugin::ABILITIES as $ability) {
            $this->assertFalse($this->plugin->canForUser($fakeUser, $ability), "Ability '{$ability}' should be denied");
        }
    }

    public function test_can_for_user_returns_false_when_no_callback(): void
    {
        $fakeUser = new \stdClass();
        $this->assertFalse($this->plugin->canForUser($fakeUser, 'viewRules'));
    }

    public function test_authorize_with_gate_is_fluent(): void
    {
        $result = $this->plugin->authorizeWithGate('firewall-admin');
        $this->assertSame($this->plugin, $result);
    }

    public function test_authorize_with_gate_sets_callback(): void
    {
        $this->plugin->authorizeWithGate('firewall-admin');

        // The callback is set (non-null), so canForUser won't return false due to null check.
        // It will try to call Gate::forUser() which won't exist in unit tests,
        // but we can verify the callback was set by checking the class internal state.
        $reflection = new \ReflectionClass($this->plugin);
        $prop = $reflection->getProperty('authorizeUsing');
        $prop->setAccessible(true);
        $this->assertNotNull($prop->getValue($this->plugin));
    }

    public function test_role_based_authorization_scenario(): void
    {
        $this->plugin->authorizeUsing(function ($user, $ability) {
            $role = $user->role ?? 'viewer';

            if ($role === 'admin') {
                return true;
            }

            if ($role === 'viewer') {
                return in_array($ability, ['viewRules', 'viewLogs']);
            }

            return false;
        });

        $admin = (object) ['role' => 'admin'];
        $viewer = (object) ['role' => 'viewer'];

        foreach (FirewallFilamentPlugin::ABILITIES as $ability) {
            $this->assertTrue($this->plugin->canForUser($admin, $ability));
        }

        $this->assertTrue($this->plugin->canForUser($viewer, 'viewRules'));
        $this->assertFalse($this->plugin->canForUser($viewer, 'mutateRules'));
        $this->assertTrue($this->plugin->canForUser($viewer, 'viewLogs'));
        $this->assertFalse($this->plugin->canForUser($viewer, 'viewSettings'));
        $this->assertFalse($this->plugin->canForUser($viewer, 'mutateSettings'));
    }
}
