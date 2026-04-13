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

    public function test_all_tags_database_entries(): void
    {
        ConfigStub::$values = [
            'firewall.whitelist' => [],
            'firewall.blacklist' => [],
        ];

        $model = $this->makeModel('10.0.0.1', true);
        Firewall::shouldReceive('all')->once()->andReturn(new Collection([$model]));

        $result = $this->adapter->all();

        $this->assertCount(1, $result);
        $entry = $result->first();
        $this->assertInstanceOf(RuleEntry::class, $entry);
        $this->assertSame('10.0.0.1', $entry->ip_address);
        $this->assertTrue($entry->whitelisted);
        $this->assertSame('database', $entry->source);
    }

    public function test_all_tags_config_entries(): void
    {
        ConfigStub::$values = [
            'firewall.whitelist' => ['192.168.1.1'],
            'firewall.blacklist' => ['10.0.0.2'],
        ];

        $models = new Collection([
            $this->makeModel('192.168.1.1', true),
            $this->makeModel('10.0.0.2', false),
            $this->makeModel('172.16.0.1', true),
        ]);
        Firewall::shouldReceive('all')->once()->andReturn($models);

        $result = $this->adapter->all();

        $this->assertCount(3, $result);
        $entries = $result->values()->all();

        $this->assertSame('config', $entries[0]->source);
        $this->assertSame('config', $entries[1]->source);
        $this->assertSame('database', $entries[2]->source);
    }

    public function test_find_returns_null_when_not_found(): void
    {
        ConfigStub::$values = [
            'firewall.whitelist' => [],
            'firewall.blacklist' => [],
        ];

        Firewall::shouldReceive('find')->with('1.2.3.4')->once()->andReturnNull();

        $this->assertNull($this->adapter->find('1.2.3.4'));
    }

    public function test_find_returns_entry_with_source(): void
    {
        ConfigStub::$values = [
            'firewall.whitelist' => ['1.2.3.4'],
            'firewall.blacklist' => [],
        ];

        $model = $this->makeModel('1.2.3.4', true);
        Firewall::shouldReceive('find')->with('1.2.3.4')->once()->andReturn($model);

        $entry = $this->adapter->find('1.2.3.4');

        $this->assertInstanceOf(RuleEntry::class, $entry);
        $this->assertSame('1.2.3.4', $entry->ip_address);
        $this->assertTrue($entry->whitelisted);
        $this->assertSame('config', $entry->source);
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
