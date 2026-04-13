<?php

namespace Magentron\LaravelFirewallFilament\Tests;

use Magentron\LaravelFirewallFilament\Support\LockoutDetector;
use PHPUnit\Framework\TestCase;

class LockoutDetectorTest extends TestCase
{
    public function test_exact_ipv4_match(): void
    {
        $this->assertTrue(LockoutDetector::wouldLockOut('192.168.1.1', '192.168.1.1'));
    }

    public function test_different_ipv4_no_match(): void
    {
        $this->assertFalse(LockoutDetector::wouldLockOut('192.168.1.1', '192.168.1.2'));
    }

    public function test_null_request_ip_returns_false(): void
    {
        $this->assertFalse(LockoutDetector::wouldLockOut('192.168.1.1', null));
    }

    public function test_empty_request_ip_returns_false(): void
    {
        $this->assertFalse(LockoutDetector::wouldLockOut('192.168.1.1', ''));
    }

    public function test_cidr_match(): void
    {
        $this->assertTrue(LockoutDetector::wouldLockOut('10.0.0.0/24', '10.0.0.42'));
    }

    public function test_cidr_no_match(): void
    {
        $this->assertFalse(LockoutDetector::wouldLockOut('10.0.0.0/24', '10.0.1.1'));
    }

    public function test_cidr_single_host(): void
    {
        $this->assertTrue(LockoutDetector::wouldLockOut('10.0.0.5/32', '10.0.0.5'));
        $this->assertFalse(LockoutDetector::wouldLockOut('10.0.0.5/32', '10.0.0.6'));
    }

    public function test_range_match(): void
    {
        $this->assertTrue(LockoutDetector::wouldLockOut('10.0.0.1-10.0.0.100', '10.0.0.50'));
    }

    public function test_range_boundary_start(): void
    {
        $this->assertTrue(LockoutDetector::wouldLockOut('10.0.0.1-10.0.0.100', '10.0.0.1'));
    }

    public function test_range_boundary_end(): void
    {
        $this->assertTrue(LockoutDetector::wouldLockOut('10.0.0.1-10.0.0.100', '10.0.0.100'));
    }

    public function test_range_no_match(): void
    {
        $this->assertFalse(LockoutDetector::wouldLockOut('10.0.0.1-10.0.0.100', '10.0.0.101'));
    }

    public function test_ipv6_exact_match(): void
    {
        $this->assertTrue(LockoutDetector::wouldLockOut('::1', '::1'));
    }

    public function test_ipv6_cidr_match(): void
    {
        $this->assertTrue(LockoutDetector::wouldLockOut('fd00::/16', 'fd00::1'));
    }

    public function test_ipv6_cidr_no_match(): void
    {
        $this->assertFalse(LockoutDetector::wouldLockOut('fd00::/16', 'fe80::1'));
    }

    public function test_country_code_no_match(): void
    {
        $this->assertFalse(LockoutDetector::wouldLockOut('country:US', '192.168.1.1'));
    }

    public function test_host_pattern_no_match(): void
    {
        $this->assertFalse(LockoutDetector::wouldLockOut('host:example.com', '192.168.1.1'));
    }

    public function test_invalid_cidr_returns_false(): void
    {
        $this->assertFalse(LockoutDetector::wouldLockOut('invalid/24', '192.168.1.1'));
    }

    public function test_invalid_range_returns_false(): void
    {
        $this->assertFalse(LockoutDetector::wouldLockOut('invalid-range', '192.168.1.1'));
    }

    public function test_case_insensitive_match(): void
    {
        $this->assertTrue(LockoutDetector::wouldLockOut('::FFFF:192.168.1.1', '::ffff:192.168.1.1'));
    }
}
