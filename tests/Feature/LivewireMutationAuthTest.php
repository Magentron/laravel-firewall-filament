<?php

namespace Magentron\LaravelFirewallFilament\Tests\Feature;

use Filament\Actions\Action;
use Magentron\LaravelFirewallFilament\Adapters\DatabaseRuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Adapters\RuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Resources\FirewallRuleResource\Pages\ManageFirewallRules;
use Magentron\LaravelFirewallFilament\Tests\TestCase;
use Mockery;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LivewireMutationAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind('firewall', fn () => new \stdClass());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_assert_mutation_allowed_denies_without_permission(): void
    {
        $page = new TestableManageFirewallRules();
        $page->allowMutations = false;

        $this->assertAborts403(fn () => $this->invokeAssertMutationAllowed($page));
    }

    public function test_assert_mutation_allowed_denies_config_sourced_record(): void
    {
        $page = new TestableManageFirewallRules();
        $page->allowMutations = true;

        $this->assertAborts403(fn () => $this->invokeAssertMutationAllowed($page, [
            'ip_address' => '10.0.0.1',
            'whitelisted' => true,
            'source' => 'config',
        ]));
    }

    public function test_assert_mutation_allowed_allows_database_record(): void
    {
        $page = new TestableManageFirewallRules();
        $page->allowMutations = true;

        $this->invokeAssertMutationAllowed($page, [
            'ip_address' => '10.0.0.1',
            'whitelisted' => true,
            'source' => 'database',
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_create_action_denies_without_permission(): void
    {
        Firewall::shouldReceive('whitelist')->never();
        Firewall::shouldReceive('blacklist')->never();

        $page = new TestableManageFirewallRules();
        $page->allowMutations = false;

        $createAction = $this->getHeaderActionNamed($page, 'create');

        $this->assertAborts403(fn () => $this->invokeActionClosure($createAction, [
            'ip_address' => '10.0.0.42',
            'whitelisted' => false,
        ]));
    }

    public function test_create_action_allows_with_permission(): void
    {
        Firewall::shouldReceive('blacklist')
            ->with('10.0.0.43', false)
            ->once()
            ->andReturn(true);

        $page = new TestableManageFirewallRules();
        $page->allowMutations = true;

        $createAction = $this->getHeaderActionNamed($page, 'create');

        $this->invokeActionClosure($createAction, [
            'ip_address' => '10.0.0.43',
            'whitelisted' => false,
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_create_action_allows_with_permission_in_database_mode(): void
    {
        $this->app['config']->set('firewall.use_database', true);

        $this->assertInstanceOf(DatabaseRuleStoreAdapter::class, app(RuleStoreAdapter::class));

        Firewall::shouldReceive('blacklist')
            ->with('10.0.0.44', false)
            ->once()
            ->andReturn(true);

        $page = new TestableManageFirewallRules();
        $page->allowMutations = true;

        $createAction = $this->getHeaderActionNamed($page, 'create');

        $this->invokeActionClosure($createAction, [
            'ip_address' => '10.0.0.44',
            'whitelisted' => false,
        ]);

        $this->addToAssertionCount(1);
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

    protected function invokeAssertMutationAllowed(TestableManageFirewallRules $page, ?array $record = null): void
    {
        $reflection = new ReflectionClass(ManageFirewallRules::class);
        $method = $reflection->getMethod('assertMutationAllowed');
        $method->setAccessible(true);
        $method->invoke($page, $record);
    }

    protected function getHeaderActionNamed(TestableManageFirewallRules $page, string $name): Action
    {
        $reflection = new ReflectionClass(ManageFirewallRules::class);
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);

        /** @var array<int, Action> $actions */
        $actions = $method->invoke($page);

        foreach ($actions as $action) {
            if ($action instanceof Action && $action->getName() === $name) {
                return $action;
            }
        }

        $this->fail("Action '{$name}' not found in ManageFirewallRules header actions.");
    }

    protected function invokeActionClosure(Action $action, ?array $data = null): void
    {
        $closure = $action->getActionFunction();

        if (! is_callable($closure)) {
            $this->fail('Action closure is not callable.');
        }

        if ($data === null) {
            $closure();

            return;
        }

        $closure($data);
    }
}

class TestableManageFirewallRules extends ManageFirewallRules
{
    public bool $allowMutations = false;

    protected function resolveAllowMutations(): bool
    {
        return $this->allowMutations;
    }
}
