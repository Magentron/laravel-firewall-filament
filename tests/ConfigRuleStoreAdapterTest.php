<?php

namespace Magentron\LaravelFirewallFilament\Tests;

use Illuminate\Support\Collection;
use Magentron\LaravelFirewallFilament\Adapters\ConfigRuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Adapters\RuleEntry;
use Magentron\LaravelFirewallFilament\Adapters\RuleStoreAdapter;
use Mockery;
use PHPUnit\Framework\TestCase;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;

class ConfigRuleStoreAdapterTest extends TestCase
{
    private ConfigRuleStoreAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new ConfigRuleStoreAdapter();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_implements_interface(): void
    {
        $this->assertInstanceOf(RuleStoreAdapter::class, $this->adapter);
    }

    public function test_all_tags_entries_as_config(): void
    {
        $models = new Collection([
            (object) ['ip_address' => '10.0.0.1', 'whitelisted' => true],
            (object) ['ip_address' => '10.0.0.2', 'whitelisted' => false],
        ]);
        Firewall::shouldReceive('all')->once()->andReturn($models);

        $result = $this->adapter->all();

        $this->assertCount(2, $result);
        foreach ($result as $entry) {
            $this->assertInstanceOf(RuleEntry::class, $entry);
            $this->assertSame('config', $entry->source);
        }
    }

    public function test_find_returns_null(): void
    {
        Firewall::shouldReceive('find')->with('1.2.3.4')->once()->andReturnNull();

        $this->assertNull($this->adapter->find('1.2.3.4'));
    }

    public function test_find_returns_config_sourced_entry(): void
    {
        $model = (object) ['ip_address' => '1.2.3.4', 'whitelisted' => false];
        Firewall::shouldReceive('find')->with('1.2.3.4')->once()->andReturn($model);

        $entry = $this->adapter->find('1.2.3.4');

        $this->assertInstanceOf(RuleEntry::class, $entry);
        $this->assertSame('config', $entry->source);
        $this->assertFalse($entry->whitelisted);
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

    public function test_remove(): void
    {
        Firewall::shouldReceive('remove')->with('1.2.3.4')->once()->andReturn(true);

        $this->assertTrue($this->adapter->remove('1.2.3.4'));
    }

    public function test_move(): void
    {
        Firewall::shouldReceive('remove')->with('1.2.3.4')->once()->andReturn(true);
        Firewall::shouldReceive('blacklist')->with('1.2.3.4', true)->once()->andReturn(true);

        $this->assertTrue($this->adapter->move('1.2.3.4', false));
    }

    public function test_warning_message(): void
    {
        $this->assertSame(
            'Firewall is running in config mode. Changes are NOT persisted beyond the current process.',
            $this->adapter->warning()
        );
    }
}
