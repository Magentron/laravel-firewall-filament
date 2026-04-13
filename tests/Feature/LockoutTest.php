<?php

namespace Magentron\LaravelFirewallFilament\Tests\Feature;

use Magentron\LaravelFirewallFilament\Support\LockoutDetector;
use Magentron\LaravelFirewallFilament\Tests\TestCase;

class LockoutTest extends TestCase
{
    public function test_lockout_detected_for_exact_ip_match(): void
    {
        $this->assertTrue(LockoutDetector::wouldLockOut('10.0.0.1', '10.0.0.1'));
    }

    public function test_no_lockout_for_different_ip(): void
    {
        $this->assertFalse(LockoutDetector::wouldLockOut('10.0.0.2', '10.0.0.1'));
    }

    public function test_lockout_detected_for_cidr_match(): void
    {
        $this->assertTrue(LockoutDetector::wouldLockOut('10.0.0.0/24', '10.0.0.50'));
    }

    public function test_no_lockout_for_cidr_outside_range(): void
    {
        $this->assertFalse(LockoutDetector::wouldLockOut('10.0.0.0/24', '10.0.1.1'));
    }

    public function test_lockout_detected_for_ip_range(): void
    {
        $this->assertTrue(LockoutDetector::wouldLockOut('10.0.0.1-10.0.0.100', '10.0.0.50'));
    }

    public function test_no_lockout_when_request_ip_is_null(): void
    {
        $this->assertFalse(LockoutDetector::wouldLockOut('10.0.0.1', null));
    }

    public function test_lockout_detection_ipv6(): void
    {
        $this->assertTrue(LockoutDetector::wouldLockOut('::1', '::1'));
    }
}
