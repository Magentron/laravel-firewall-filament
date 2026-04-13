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
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Regression guard: even if a crafted Livewire request bypasses the
 * UI-level `->visible()` hide, the create action's server-side guard
 * MUST abort(403) when the current user lacks mutateRules. This is the
 * second line of defence added after the earlier "create action had no
 * server-side check" finding — the reviewer explicitly asked for a test
 * that exercises the *action invocation path*, not just the UI visibility.
 *
 * Approach: we resolve the ManageFirewallRules `create` header action via
 * reflection and invoke its action closure directly with crafted data. That
 * is the exact closure Livewire::test()->callAction() would reach at the end
 * of its mount-then-invoke dance, but without the full Filament panel
 * bootstrap (which requires HTTP session state, view error bag wiring, and
 * a rendered page) that is out of scope for a package-level unit suite.
 */
class LivewireCreateAuthTest extends TestCase
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

        $this->app->bind('firewall', fn () => new \stdClass());
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

    public function test_create_action_aborts_when_user_lacks_mutate_rules(): void
    {
        // Grant viewRules but deliberately withhold mutateRules.
        $this->app['config']->set('test.firewall_filament.abilities', ['viewRules']);

        $this->actingAs($this->stubUser());

        // If the guard works, Firewall::whitelist/blacklist must NEVER be called.
        Firewall::shouldReceive('whitelist')->never();
        Firewall::shouldReceive('blacklist')->never();

        $createAction = $this->getCreateAction();

        $aborted = false;

        try {
            $this->invokeActionClosure($createAction, [
                'ip_address' => '10.0.0.42',
                'whitelisted' => false,
            ]);
        } catch (HttpException $e) {
            $aborted = true;
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertTrue(
            $aborted,
            'Create action should have aborted with 403 when user lacks mutateRules',
        );
    }

    public function test_create_action_succeeds_when_user_has_mutate_rules(): void
    {
        $this->app['config']->set('test.firewall_filament.abilities', ['viewRules', 'mutateRules']);

        $this->actingAs($this->stubUser());

        Firewall::shouldReceive('blacklist')
            ->with('10.0.0.43', false)
            ->once()
            ->andReturn(true);

        // Lockout detection uses request()->ip() which resolves to 127.0.0.1
        // in testbench, so 10.0.0.43 will not be flagged.
        $createAction = $this->getCreateAction();

        $this->invokeActionClosure($createAction, [
            'ip_address' => '10.0.0.43',
            'whitelisted' => false,
        ]);

        // If we got here, the action completed without abort(403). Mockery's
        // expectation assertion happens in tearDown.
        $this->assertTrue(true);
    }

    public function test_plugin_denies_mutate_rules_when_only_view_is_granted(): void
    {
        // Guard rail: confirms the TestPanelProvider's per-test ability config
        // actually flows through to FirewallFilamentPlugin::can(). If this test
        // fails, the other tests in this class are not meaningful.
        $this->app['config']->set('test.firewall_filament.abilities', ['viewRules']);

        $this->actingAs($this->stubUser());

        $plugin = Filament::getPanel('testing')->getPlugin('magentron-laravel-firewall-filament');
        $this->assertInstanceOf(FirewallFilamentPlugin::class, $plugin);

        $this->assertTrue($plugin->can('viewRules'));
        $this->assertFalse($plugin->can('mutateRules'));
    }

    /**
     * Instantiate the page and pull the 'create' header action out of
     * getHeaderActions(). The method is protected, so we bypass visibility
     * via reflection — the production action machinery is not mocked, only
     * accessed.
     */
    private function getCreateAction(): Action
    {
        $page = new ManageFirewallRules();

        $reflection = new ReflectionClass($page);
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);

        /** @var array<int, Action> $actions */
        $actions = $method->invoke($page);

        foreach ($actions as $action) {
            if ($action instanceof Action && $action->getName() === 'create') {
                return $action;
            }
        }

        $this->fail('Create action not found in ManageFirewallRules header actions.');
    }

    /**
     * Invoke the action's registered closure directly. Filament's Action
     * stores the callback via ->action($closure); we retrieve it via the
     * public getAction() accessor and call it with the form data the UI
     * would have collected.
     */
    private function invokeActionClosure(Action $action, array $data): void
    {
        $closure = $action->getActionFunction();

        if (! is_callable($closure)) {
            $this->fail('Action closure is not callable.');
        }

        // Filament injects dependencies into the closure from an evaluator
        // container. We emulate the minimal contract: pass the form data
        // array as the single `$data` named parameter, which matches the
        // closure's signature in ManageFirewallRules::getHeaderActions().
        $closure($data);
    }

    private function stubUser(): Authenticatable
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
