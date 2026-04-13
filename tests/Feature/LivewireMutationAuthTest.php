<?php

namespace Magentron\LaravelFirewallFilament\Tests\Feature;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Schema;
use Magentron\LaravelFirewallFilament\FirewallFilamentPlugin;
use Magentron\LaravelFirewallFilament\Resources\FirewallRuleResource\Pages\ManageFirewallRules;
use Magentron\LaravelFirewallFilament\Tests\Support\TestPanelProvider;
use Magentron\LaravelFirewallFilament\Tests\TestCase;
use Mockery;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;
use ReflectionClass;
use stdClass;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Regression guard for the server-side `abort(403)` that every mutating
 * action on ManageFirewallRules now funnels through.
 *
 * Two layers of coverage:
 *
 *   1. `assertMutationAllowed()` helper test — this is the single source
 *      of truth for action-level authorization. Every mutating closure
 *      (create, move, delete, bulk-delete, clearAll) calls it, so one
 *      focused test on the helper guards every action path at once.
 *
 *   2. Direct reflection test on the `create` and `clearAll` header
 *      actions. These actions live in `getHeaderActions()` which is
 *      reachable without bootstrapping a live Table. Per-row actions
 *      (move, delete) and bulk actions (bulk delete) live inside the
 *      `table()` builder closure and would require a full table render
 *      to reach; they are covered transitively via the helper test.
 */
class LivewireMutationAuthTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return array_merge(
            parent::getPackageProviders($app),
            [
                \Livewire\LivewireServiceProvider::class,
                \Filament\Support\SupportServiceProvider::class,
                \Filament\Actions\ActionsServiceProvider::class,
                \Filament\Forms\FormsServiceProvider::class,
                \Filament\Infolists\InfolistsServiceProvider::class,
                \Filament\Notifications\NotificationsServiceProvider::class,
                \Filament\Schemas\SchemasServiceProvider::class,
                \Filament\Tables\TablesServiceProvider::class,
                \Filament\Widgets\WidgetsServiceProvider::class,
                \Filament\FilamentServiceProvider::class,
                TestPanelProvider::class,
            ],
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind('firewall', fn () => new stdClass());
        $this->app['config']->set('firewall.use_database', true);
        $this->app['config']->set('firewall.whitelist', []);
        $this->app['config']->set('firewall.blacklist', []);

        if (! Schema::hasTable('firewall')) {
            Schema::create('firewall', function ($table) {
                $table->increments('id');
                $table->string('ip_address', 39)->unique();
                $table->boolean('whitelisted')->default(false);
                $table->timestamps();
            });
        }

        Filament::setCurrentPanel(Filament::getPanel('testing'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_assert_mutation_allowed_throws_when_user_lacks_mutate_rules(): void
    {
        // Helper reads authorization LIVE via $this->resolveAllowMutations(),
        // so we set the plugin's per-test ability config and authenticate
        // as a stub user before invocation.
        $this->app['config']->set('test.firewall_filament.abilities', ['viewRules']);
        $this->actingAs($this->stubUser());

        $page = new ManageFirewallRules();

        $this->assertAborts403(fn () => $this->invokeAssertMutationAllowed($page));
    }

    public function test_assert_mutation_allowed_throws_on_config_sourced_row(): void
    {
        $this->app['config']->set('test.firewall_filament.abilities', ['viewRules', 'mutateRules']);
        $this->actingAs($this->stubUser());

        $page = new ManageFirewallRules();

        $this->assertAborts403(fn () => $this->invokeAssertMutationAllowed($page, [
            'ip_address'  => '10.0.0.42',
            'whitelisted' => false,
            'source'      => 'config',
        ]));
    }

    public function test_assert_mutation_allowed_passes_on_database_row(): void
    {
        // No exception expected; if the helper lets this through, the
        // call below returns void and the test succeeds by absence of
        // exception.
        $this->app['config']->set('test.firewall_filament.abilities', ['viewRules', 'mutateRules']);
        $this->actingAs($this->stubUser());

        $page = new ManageFirewallRules();

        $this->invokeAssertMutationAllowed($page, [
            'ip_address'  => '10.0.0.42',
            'whitelisted' => false,
            'source'      => 'database',
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_assert_mutation_allowed_passes_without_record(): void
    {
        // Exercised by bulk-delete and clearAll, which call the helper
        // without a record (they pre-filter config-sourced entries in
        // their own loops instead).
        $this->app['config']->set('test.firewall_filament.abilities', ['viewRules', 'mutateRules']);
        $this->actingAs($this->stubUser());

        $page = new ManageFirewallRules();

        $this->invokeAssertMutationAllowed($page);

        $this->addToAssertionCount(1);
    }

    public function test_assert_mutation_allowed_honours_live_permission_revocation(): void
    {
        // Regression guard for the live-re-evaluation behaviour of the
        // helper: a permission granted at setUp() time must NOT "stick"
        // across a revocation. The helper MUST re-query the plugin on
        // every call, so flipping the ability config mid-test must cause
        // the next call to abort — on the SAME $page instance, so the
        // behaviour cannot be explained away by "first call aborted on
        // a fresh page object".
        //
        // This test would silently pass for the wrong reason if
        // `FirewallFilamentPlugin::can()` ever started memoising the
        // ability set internally; as of this commit it does not (see
        // src/FirewallFilamentPlugin.php — `can()` re-invokes
        // `canForUser()` which re-invokes the `authorizeUsing` closure
        // which reads `config('test.firewall_filament.abilities')`
        // fresh). If a future refactor introduces per-instance memoisation
        // of the plugin's ability resolution, this test must be updated
        // to bust that cache between the two calls.
        $this->app['config']->set('test.firewall_filament.abilities', ['viewRules', 'mutateRules']);
        $this->actingAs($this->stubUser());

        $page = new ManageFirewallRules();

        // Explicit: first call MUST pass on the same page instance that
        // the revoked second call will use. A `addToAssertionCount(1)`
        // here makes the intent unmistakable — if this call ever starts
        // throwing, the test has become meaningless and the failure
        // output will point at this exact line rather than the revoked
        // second call.
        $this->invokeAssertMutationAllowed($page);
        $this->addToAssertionCount(1);

        // Revoke mutateRules mid-session on the SAME page instance.
        $this->app['config']->set('test.firewall_filament.abilities', ['viewRules']);

        $this->assertAborts403(fn () => $this->invokeAssertMutationAllowed($page));
    }

    public function test_create_action_aborts_when_user_lacks_mutate_rules(): void
    {
        // Grant viewRules but deliberately withhold mutateRules.
        $this->app['config']->set('test.firewall_filament.abilities', ['viewRules']);

        $this->actingAs($this->stubUser());

        // If the guard works, Firewall::whitelist/blacklist must NEVER be called.
        Firewall::shouldReceive('whitelist')->never();
        Firewall::shouldReceive('blacklist')->never();

        $createAction = $this->getHeaderActionNamed('create');

        $this->assertAborts403(fn () => $this->invokeActionClosure($createAction, [
            'ip_address'  => '10.0.0.42',
            'whitelisted' => false,
        ]));
    }

    public function test_create_action_succeeds_when_user_has_mutate_rules(): void
    {
        $this->app['config']->set('test.firewall_filament.abilities', ['viewRules', 'mutateRules']);

        $this->actingAs($this->stubUser());

        // Mockery's `->once()` expectation IS the real assertion here —
        // it is verified by Mockery::close() in tearDown. We do not need
        // an explicit assertTrue(true).
        Firewall::shouldReceive('blacklist')
            ->with('10.0.0.43', false)
            ->once()
            ->andReturn(true);

        // Lockout detection uses request()->ip() which resolves to
        // 127.0.0.1 in testbench, so 10.0.0.43 will not be flagged.
        $createAction = $this->getHeaderActionNamed('create');

        $this->invokeActionClosure($createAction, [
            'ip_address'  => '10.0.0.43',
            'whitelisted' => false,
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_clear_all_action_aborts_when_user_lacks_mutate_rules(): void
    {
        $this->app['config']->set('test.firewall_filament.abilities', ['viewRules']);

        $this->actingAs($this->stubUser());

        Firewall::shouldReceive('remove')->never();
        Firewall::shouldReceive('clear')->never();

        $clearAction = $this->getHeaderActionNamed('clearAll');

        $this->assertAborts403(fn () => $this->invokeActionClosure($clearAction));
    }

    public function test_plugin_denies_mutate_rules_when_only_view_is_granted(): void
    {
        // Guard rail: confirms the TestPanelProvider's per-test ability
        // config actually flows through to FirewallFilamentPlugin::can().
        // If this test fails, the other tests in this class are not
        // meaningful.
        $this->app['config']->set('test.firewall_filament.abilities', ['viewRules']);

        $this->actingAs($this->stubUser());

        $plugin = Filament::getPanel('testing')->getPlugin('magentron-laravel-firewall-filament');
        $this->assertInstanceOf(FirewallFilamentPlugin::class, $plugin);

        $this->assertTrue($plugin->can('viewRules'));
        $this->assertFalse($plugin->can('mutateRules'));
    }

    /**
     * Run a callable and assert that it aborts with an HTTP 403. Symfony's
     * `HttpException` stores the status via `getStatusCode()`, not
     * `getCode()`, so PHPUnit's native `expectExceptionCode()` would
     * spuriously fail against the default `0`.
     */
    protected function assertAborts403(callable $callable): void
    {
        try {
            $callable();
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode(), 'Expected HTTP 403, got ' . $e->getStatusCode());

            return;
        }

        $this->fail('Expected HttpException with status 403, none was thrown.');
    }

    /**
     * Invoke the protected `assertMutationAllowed()` helper via
     * reflection. The helper itself is the single enforcement point
     * every mutating action closure delegates to, so testing it directly
     * guards all five closures at once. The helper reads authorization
     * state LIVE from the plugin, so the caller must arrange the
     * `test.firewall_filament.abilities` config and `actingAs()` stub
     * user before invocation.
     */
    protected function invokeAssertMutationAllowed(
        ManageFirewallRules $page,
        ?array $record = null,
    ): void {
        $reflection = new ReflectionClass($page);
        $method     = $reflection->getMethod('assertMutationAllowed');
        $method->setAccessible(true);
        $method->invoke($page, $record);
    }

    /**
     * Pull a header action by name out of `getHeaderActions()`. The
     * method is protected, so we bypass visibility via reflection.
     */
    protected function getHeaderActionNamed(string $name): Action
    {
        $page       = new ManageFirewallRules();
        $reflection = new ReflectionClass($page);
        $method     = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);

        /** @var array<int, Action> $actions */
        $actions = $method->invoke($page);

        foreach ($actions as $action) {
            if ($action instanceof Action && $name === $action->getName()) {
                return $action;
            }
        }

        $this->fail("Action '{$name}' not found in ManageFirewallRules header actions.");
    }

    /**
     * Invoke the action's registered closure directly. This is the exact
     * closure Livewire would reach at the end of its mount-then-invoke
     * dance, minus the rendered-page bootstrap.
     */
    protected function invokeActionClosure(Action $action, array $data = []): void
    {
        $closure = $action->getActionFunction();

        if (! is_callable($closure)) {
            $this->fail('Action closure is not callable.');
        }

        $closure($data);
    }

    protected function stubUser(): Authenticatable
    {
        return new class implements Authenticatable {
            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthIdentifier(): mixed
            {
                return 1;
            }

            public function getAuthPasswordName(): string
            {
                return 'password';
            }

            public function getAuthPassword(): string
            {
                return '';
            }

            public function getRememberToken(): string
            {
                return '';
            }

            public function setRememberToken($value): void
            {
            }

            public function getRememberTokenName(): string
            {
                return '';
            }
        };
    }
}
