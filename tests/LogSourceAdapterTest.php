<?php

namespace Magentron\LaravelFirewallFilament\Tests;

use Magentron\LaravelFirewallFilament\Adapters\LaravelLogFileAdapter;
use Magentron\LaravelFirewallFilament\Adapters\LogSourceAdapter;
use Magentron\LaravelFirewallFilament\Adapters\NullLogSourceAdapter;
use PHPUnit\Framework\TestCase;

class LogSourceAdapterTest extends TestCase
{
    public function test_null_adapter_implements_interface(): void
    {
        $adapter = new NullLogSourceAdapter();
        $this->assertInstanceOf(LogSourceAdapter::class, $adapter);
    }

    public function test_null_adapter_not_supported(): void
    {
        $adapter = new NullLogSourceAdapter();
        $this->assertFalse($adapter->supported());
    }

    public function test_null_adapter_returns_empty(): void
    {
        $adapter = new NullLogSourceAdapter();
        $entries = $adapter->recentEntries(10);
        $this->assertCount(0, $entries);
    }

    public function test_log_file_adapter_implements_interface(): void
    {
        $adapter = new LaravelLogFileAdapter('/nonexistent/path.log');
        $this->assertInstanceOf(LogSourceAdapter::class, $adapter);
    }

    public function test_log_file_adapter_not_supported_for_missing_file(): void
    {
        $adapter = new LaravelLogFileAdapter('/nonexistent/path.log');
        $this->assertFalse($adapter->supported());
    }

    public function test_log_file_adapter_not_supported_for_empty_path(): void
    {
        $adapter = new LaravelLogFileAdapter('');
        $this->assertFalse($adapter->supported());
    }

    public function test_log_file_adapter_returns_empty_for_missing_file(): void
    {
        $adapter = new LaravelLogFileAdapter('/nonexistent/path.log');
        $this->assertCount(0, $adapter->recentEntries(10));
    }

    public function test_log_file_adapter_reads_firewall_entries(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'fw_test_');
        file_put_contents($tmpFile, implode("\n", [
            '[2026-04-13] local.INFO: Some other log line',
            '[2026-04-13] local.INFO: FIREWALL: Blocked 1.2.3.4',
            '[2026-04-13] local.INFO: Another line',
            '[2026-04-13] local.INFO: FIREWALL: Allowed 5.6.7.8',
            '[2026-04-13] local.INFO: FIREWALL: Blocked 9.10.11.12',
        ]));

        try {
            $adapter = new LaravelLogFileAdapter($tmpFile);

            $this->assertTrue($adapter->supported());

            $entries = $adapter->recentEntries(10);
            $entries = is_array($entries) ? $entries : iterator_to_array($entries);

            $this->assertCount(3, $entries);
            $this->assertStringContainsString('FIREWALL: Blocked 1.2.3.4', $entries[0]);
            $this->assertStringContainsString('FIREWALL: Allowed 5.6.7.8', $entries[1]);
            $this->assertStringContainsString('FIREWALL: Blocked 9.10.11.12', $entries[2]);
        } finally {
            unlink($tmpFile);
        }
    }

    public function test_log_file_adapter_respects_limit(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'fw_test_');
        file_put_contents($tmpFile, implode("\n", [
            '[2026-04-13] local.INFO: FIREWALL: Entry 1',
            '[2026-04-13] local.INFO: FIREWALL: Entry 2',
            '[2026-04-13] local.INFO: FIREWALL: Entry 3',
        ]));

        try {
            $adapter = new LaravelLogFileAdapter($tmpFile);
            $entries = $adapter->recentEntries(2);
            $entries = is_array($entries) ? $entries : iterator_to_array($entries);

            $this->assertCount(2, $entries);
            $this->assertStringContainsString('Entry 2', $entries[0]);
            $this->assertStringContainsString('Entry 3', $entries[1]);
        } finally {
            unlink($tmpFile);
        }
    }

    public function test_log_file_adapter_handles_empty_file(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'fw_test_');
        file_put_contents($tmpFile, '');

        try {
            $adapter = new LaravelLogFileAdapter($tmpFile);
            $this->assertCount(0, $adapter->recentEntries(10));
        } finally {
            unlink($tmpFile);
        }
    }
}
