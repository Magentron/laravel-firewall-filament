<?php

namespace Magentron\LaravelFirewallFilament\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Magentron\LaravelFirewallFilament\Adapters\DatabaseRuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Adapters\RuleEntry;
use Magentron\LaravelFirewallFilament\Adapters\RuleStoreAdapter;
use Mockery;
use PHPUnit\Framework\TestCase;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;

class DatabaseRuleStoreAdapterTest extends TestCase
{
    private DatabaseRuleStoreAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new DatabaseRuleStoreAdapter();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        ConfigStub::$values = [];
        parent::tearDown();
    }

    public function test_implements_interface(): void
    {
        $this->assertInstanceOf(RuleStoreAdapter::class, $this->adapter);
    }

    public function test_add_whitelist(): void
    {
        Firewall::shouldReceive('whitelist')->with('1.2.3.4', false)->once()->andReturn(true);

        $this->assertTrue($this->adapter->add('1.2.3.4', true));
    }

    public function test_add_blacklist(): void
    {
        Firewall::shouldReceive('blacklist')->with('1.2.3.4', false)->once()->andReturn(true);

        $this->assertTrue($this->adapter->add('1.2.3.4', false));
    }

    public function test_add_with_force(): void
    {
        Firewall::shouldReceive('whitelist')->with('1.2.3.4', true)->once()->andReturn(true);

        $this->assertTrue($this->adapter->add('1.2.3.4', true, true));
    }

    public function test_remove(): void
    {
        Firewall::shouldReceive('remove')->with('1.2.3.4')->once()->andReturn(true);

        $this->assertTrue($this->adapter->remove('1.2.3.4'));
    }

    public function test_move_calls_remove_then_add_with_force(): void
    {
        Firewall::shouldReceive('remove')->with('1.2.3.4')->once()->andReturn(true);
        Firewall::shouldReceive('whitelist')->with('1.2.3.4', true)->once()->andReturn(true);

        $this->assertTrue($this->adapter->move('1.2.3.4', true));
    }

    public function test_move_to_blacklist(): void
    {
        Firewall::shouldReceive('remove')->with('1.2.3.4')->once()->andReturn(true);
        Firewall::shouldReceive('blacklist')->with('1.2.3.4', true)->once()->andReturn(true);

        $this->assertTrue($this->adapter->move('1.2.3.4', false));
    }

    private function makeModel(string $ip, bool $whitelisted): object
    {
        return (object) [
            'ip_address' => $ip,
            'whitelisted' => $whitelisted,
        ];
    }
}
