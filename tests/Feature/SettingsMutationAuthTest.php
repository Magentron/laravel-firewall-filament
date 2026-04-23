<?php

namespace Magentron\LaravelFirewallFilament\Tests\Feature;

use Magentron\LaravelFirewallFilament\Pages\FirewallSettingsPage;
use Magentron\LaravelFirewallFilament\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SettingsMutationAuthTest extends TestCase
{
    public function test_save_denies_without_mutate_settings(): void
    {
        $page = new TestableFirewallSettingsPage();
        $page->allowMutations = false;

        $this->assertAborts403(fn () => $page->save());
    }

    public function test_restore_snapshot_denies_without_mutate_settings(): void
    {
        $page = new TestableFirewallSettingsPage();
        $page->allowMutations = false;

        $this->assertAborts403(fn () => $page->restoreSnapshot('snapshot-1'));
    }

    protected function assertAborts403(callable $callable): void
    {
        try {
            $callable();
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());

            return;
        }

        $this->fail('Expected HttpException with status 403, none was thrown.');
    }
}

class TestableFirewallSettingsPage extends FirewallSettingsPage
{
    public bool $allowMutations = false;

    public function canMutateSettings(): bool
    {
        return $this->allowMutations;
    }
}
