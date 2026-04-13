<?php

namespace Magentron\LaravelFirewallFilament\Tests;

use Magentron\LaravelFirewallFilament\Models\AuditLog;
use Magentron\LaravelFirewallFilament\Support\AuditLogger;
use PHPUnit\Framework\TestCase;

class AuditLoggerTest extends TestCase
{
    public function test_audit_logger_can_be_instantiated(): void
    {
        $logger = new AuditLogger();
        $this->assertInstanceOf(AuditLogger::class, $logger);
    }

    public function test_audit_log_model_table_name(): void
    {
        $model = new AuditLog();
        $this->assertSame('firewall_filament_audit', $model->getTable());
    }

    public function test_audit_log_model_has_no_timestamps(): void
    {
        $model = new AuditLog();
        $this->assertFalse($model->usesTimestamps());
    }

    public function test_audit_log_model_fillable_fields(): void
    {
        $model = new AuditLog();
        $this->assertSame([
            'user_id',
            'ability',
            'action',
            'target',
            'before',
            'after',
            'created_at',
        ], $model->getFillable());
    }

    public function test_audit_log_model_casts(): void
    {
        $model = new AuditLog();
        $casts = $model->getCasts();

        $this->assertSame('array', $casts['before']);
        $this->assertSame('array', $casts['after']);
        $this->assertSame('datetime', $casts['created_at']);
    }

    public function test_current_user_id_returns_null_without_filament(): void
    {
        $this->assertNull(AuditLogger::currentUserId());
    }
}
