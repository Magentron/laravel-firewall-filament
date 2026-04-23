<?php

namespace Magentron\LaravelFirewallFilament\Tests\Feature;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Magentron\LaravelFirewallFilament\Adapters\DatabaseRuleStoreAdapter;
use Magentron\LaravelFirewallFilament\Adapters\RuleEntry;
use Magentron\LaravelFirewallFilament\Tests\TestCase;
use Mockery;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;
use PragmaRX\Firewall\Vendor\Laravel\Models\Firewall as FirewallModel;

class RuleCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind('firewall', fn () => new \stdClass());

        if (! Schema::hasTable('firewall')) {
            Schema::create('firewall', function ($table) {
                $table->increments('id');
                $table->string('ip_address', 39)->unique();
                $table->boolean('whitelisted')->default(false);
                $table->timestamps();
            });
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_all_rules(): void
    {
        $this->app['config']->set('firewall.whitelist', []);
        $this->app['config']->set('firewall.blacklist', []);

        $models = new Collection([
            (object) ['ip_address' => '10.0.0.1', 'whitelisted' => true],
            (object) ['ip_address' => '10.0.0.2', 'whitelisted' => false],
        ]);
        Firewall::shouldReceive('all')->once()->andReturn($models);

        $adapter = new DatabaseRuleStoreAdapter();
        $result = $adapter->all();

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(RuleEntry::class, $result);
    }

    public function test_create_whitelist_rule(): void
    {
        Firewall::shouldReceive('whitelist')->with('192.168.1.1', false)->once()->andReturn(true);

        $adapter = new DatabaseRuleStoreAdapter();
        $this->assertTrue($adapter->add('192.168.1.1', true));
    }

    public function test_create_blacklist_rule(): void
    {
        Firewall::shouldReceive('blacklist')->with('10.0.0.5', false)->once()->andReturn(true);

        $adapter = new DatabaseRuleStoreAdapter();
        $this->assertTrue($adapter->add('10.0.0.5', false));
    }

    public function test_move_rule_between_lists(): void
    {
        Firewall::shouldReceive('find')->with('10.0.0.1')->once()->andReturnNull();
        Firewall::shouldReceive('remove')->with('10.0.0.1')->once()->andReturn(true);
        Firewall::shouldReceive('blacklist')->with('10.0.0.1', true)->once()->andReturn(true);

        $adapter = new DatabaseRuleStoreAdapter();
        $this->assertTrue($adapter->move('10.0.0.1', false));
    }

    public function test_delete_rule(): void
    {
        Firewall::shouldReceive('find')->with('10.0.0.1')->once()->andReturnNull();
        Firewall::shouldReceive('remove')->with('10.0.0.1')->once()->andReturn(true);

        $adapter = new DatabaseRuleStoreAdapter();
        $this->assertTrue($adapter->remove('10.0.0.1'));
    }

    public function test_cannot_delete_config_sourced_entry(): void
    {
        $this->app['config']->set('firewall.use_database', true);

        $model = (object) ['ip_address' => '10.0.0.1', 'whitelisted' => true];
        Firewall::shouldReceive('find')->with('10.0.0.1')->once()->andReturn($model);
        Firewall::shouldNotReceive('remove');

        $adapter = new DatabaseRuleStoreAdapter();
        $this->assertFalse($adapter->remove('10.0.0.1'));
    }

    public function test_distinguishes_config_from_database_entries(): void
    {
        $this->app['config']->set('firewall.use_database', true);
        $this->app['config']->set('firewall.whitelist', ['10.0.0.1']);
        $this->app['config']->set('firewall.blacklist', []);

        FirewallModel::query()->insert([
            'ip_address' => '10.0.0.2',
            'whitelisted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $models = new Collection([
            (object) ['ip_address' => '10.0.0.1', 'whitelisted' => true],
            (object) ['ip_address' => '10.0.0.2', 'whitelisted' => false],
        ]);
        Firewall::shouldReceive('all')->once()->andReturn($models);

        $adapter = new DatabaseRuleStoreAdapter();
        $entries = $adapter->all()->values();

        $this->assertSame('config', $entries[0]->source);
        $this->assertSame('database', $entries[1]->source);
    }

    public function test_find_returns_null_when_not_found(): void
    {
        $this->app['config']->set('firewall.whitelist', []);
        $this->app['config']->set('firewall.blacklist', []);

        Firewall::shouldReceive('find')->with('1.2.3.4')->once()->andReturnNull();

        $adapter = new DatabaseRuleStoreAdapter();
        $this->assertNull($adapter->find('1.2.3.4'));
    }

    public function test_find_returns_entry_with_source_tag(): void
    {
        $this->app['config']->set('firewall.use_database', true);
        $this->app['config']->set('firewall.whitelist', ['1.2.3.4']);
        $this->app['config']->set('firewall.blacklist', []);

        $model = (object) ['ip_address' => '1.2.3.4', 'whitelisted' => true];
        Firewall::shouldReceive('find')->with('1.2.3.4')->once()->andReturn($model);

        $adapter = new DatabaseRuleStoreAdapter();
        $entry = $adapter->find('1.2.3.4');

        $this->assertInstanceOf(RuleEntry::class, $entry);
        $this->assertSame('config', $entry->source);
    }
}
