<?php

namespace Magentron\LaravelFirewallFilament\Tests\Feature;

use Magentron\LaravelFirewallFilament\Models\AuditLog;
use Magentron\LaravelFirewallFilament\Support\AuditLogger;
use Magentron\LaravelFirewallFilament\Tests\TestCase;

class AuditTrailTest extends TestCase
{
    public function test_audit_logger_writes_to_database(): void
    {
        $logger = $this->app->make(AuditLogger::class);

        $record = $logger->log(
            userId: 1,
            ability: 'mutateRules',
            action: 'add',
            target: '10.0.0.1',
            after: ['list' => 'whitelist'],
        );

        $this->assertDatabaseHas('firewall_filament_audit', [
            'user_id' => 1,
            'ability' => 'mutateRules',
            'action' => 'add',
            'target' => '10.0.0.1',
        ]);

        $this->assertInstanceOf(AuditLog::class, $record);
    }

    public function test_audit_log_stores_before_and_after(): void
    {
        $logger = $this->app->make(AuditLogger::class);

        $logger->log(
            userId: 2,
            ability: 'mutateRules',
            action: 'move',
            target: '192.168.1.1',
            before: ['list' => 'whitelist'],
            after: ['list' => 'blacklist'],
        );

        $log = AuditLog::where('target', '192.168.1.1')->first();

        $this->assertSame(['list' => 'whitelist'], $log->before);
        $this->assertSame(['list' => 'blacklist'], $log->after);
    }

    public function test_audit_log_with_null_user_id(): void
    {
        $logger = $this->app->make(AuditLogger::class);

        $logger->log(
            userId: null,
            ability: 'mutateRules',
            action: 'remove',
            target: '10.0.0.5',
        );

        $this->assertDatabaseHas('firewall_filament_audit', [
            'user_id' => null,
            'action' => 'remove',
            'target' => '10.0.0.5',
        ]);
    }

    public function test_audit_log_records_are_queryable(): void
    {
        $logger = $this->app->make(AuditLogger::class);

        $logger->log(1, 'mutateRules', 'add', '10.0.0.1');
        $logger->log(1, 'mutateRules', 'remove', '10.0.0.2');
        $logger->log(2, 'mutateSettings', 'settings_change');

        $this->assertSame(2, AuditLog::where('ability', 'mutateRules')->count());
        $this->assertSame(1, AuditLog::where('ability', 'mutateSettings')->count());
        $this->assertSame(3, AuditLog::count());
    }

    public function test_audit_log_has_timestamp(): void
    {
        $logger = $this->app->make(AuditLogger::class);
        $record = $logger->log(1, 'mutateRules', 'add', '10.0.0.1');

        $this->assertNotNull($record->created_at);
    }

    public function test_clear_action_records_before_state(): void
    {
        $logger = $this->app->make(AuditLogger::class);

        $before = [
            ['ip' => '10.0.0.1', 'list' => 'whitelist'],
            ['ip' => '10.0.0.2', 'list' => 'blacklist'],
        ];

        $logger->log(1, 'mutateRules', 'clear', null, $before);

        $log = AuditLog::where('action', 'clear')->first();
        $this->assertSame($before, $log->before);
        $this->assertNull($log->target);
    }
}
