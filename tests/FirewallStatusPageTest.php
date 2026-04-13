<?php

namespace Magentron\LaravelFirewallFilament\Tests;

use Magentron\LaravelFirewallFilament\Support\LogEntryParser;
use PHPUnit\Framework\TestCase;

class FirewallStatusPageTest extends TestCase
{
    public function test_parse_timestamp_standard_laravel_format(): void
    {
        $line = '[2024-01-15 10:30:45] local.INFO: FIREWALL: blocked 1.2.3.4';
        $this->assertSame('2024-01-15 10:30:45', LogEntryParser::parseTimestamp($line));
    }

    public function test_parse_timestamp_with_timezone(): void
    {
        $line = '[2024-01-15T10:30:45+00:00] local.INFO: FIREWALL: blocked 1.2.3.4';
        $this->assertSame('2024-01-15T10:30:45+00:00', LogEntryParser::parseTimestamp($line));
    }

    public function test_parse_timestamp_with_microseconds(): void
    {
        $line = '[2024-01-15 10:30:45.123456] local.INFO: FIREWALL: blocked 1.2.3.4';
        $this->assertSame('2024-01-15 10:30:45.123456', LogEntryParser::parseTimestamp($line));
    }

    public function test_parse_timestamp_returns_null_for_no_timestamp(): void
    {
        $line = 'FIREWALL: blocked 1.2.3.4';
        $this->assertNull(LogEntryParser::parseTimestamp($line));
    }

    public function test_parse_timestamp_returns_null_for_empty_string(): void
    {
        $this->assertNull(LogEntryParser::parseTimestamp(''));
    }

    public function test_parse_timestamp_with_iso_format(): void
    {
        $line = '[2024-01-15T10:30:45] production.WARNING: FIREWALL: suspicious 5.6.7.8';
        $this->assertSame('2024-01-15T10:30:45', LogEntryParser::parseTimestamp($line));
    }
}
