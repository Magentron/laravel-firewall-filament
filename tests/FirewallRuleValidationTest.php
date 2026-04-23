<?php

namespace Magentron\LaravelFirewallFilament\Tests;

use Magentron\LaravelFirewallFilament\Rules\FirewallEntryRule;
use PHPUnit\Framework\TestCase;

class FirewallRuleValidationTest extends TestCase
{
    public function test_documented_contract_formats_are_accepted(): void
    {
        $this->assertTrue(FirewallEntryRule::isValid('203.0.113.10'));
        $this->assertTrue(FirewallEntryRule::isValid('2001:db8::10'));
        $this->assertTrue(FirewallEntryRule::isValid('203.0.113.0/24'));
        $this->assertTrue(FirewallEntryRule::isValid('2001:db8::/32'));
        $this->assertTrue(FirewallEntryRule::isValid('203.0.113.10-203.0.113.20'));
        $this->assertTrue(FirewallEntryRule::isValid('country:NL'));
        $this->assertTrue(FirewallEntryRule::isValid('host:example.com'));
    }

    public function test_single_ipv4_is_valid(): void
    {
        $this->assertTrue(FirewallEntryRule::isValid('192.168.1.1'));
        $this->assertTrue(FirewallEntryRule::isValid('10.0.0.1'));
        $this->assertTrue(FirewallEntryRule::isValid('255.255.255.255'));
        $this->assertTrue(FirewallEntryRule::isValid('0.0.0.0'));
    }

    public function test_single_ipv6_is_valid(): void
    {
        $this->assertTrue(FirewallEntryRule::isValid('::1'));
        $this->assertTrue(FirewallEntryRule::isValid('2001:db8::1'));
        $this->assertTrue(FirewallEntryRule::isValid('fe80::1'));
    }

    public function test_cidr_ipv4_is_valid(): void
    {
        $this->assertTrue(FirewallEntryRule::isValid('10.0.0.0/8'));
        $this->assertTrue(FirewallEntryRule::isValid('192.168.1.0/24'));
        $this->assertTrue(FirewallEntryRule::isValid('172.16.0.0/12'));
        $this->assertTrue(FirewallEntryRule::isValid('0.0.0.0/0'));
        $this->assertTrue(FirewallEntryRule::isValid('10.0.0.1/32'));
    }

    public function test_cidr_ipv6_is_valid(): void
    {
        $this->assertTrue(FirewallEntryRule::isValid('2001:db8::/32'));
        $this->assertTrue(FirewallEntryRule::isValid('fe80::/10'));
        $this->assertTrue(FirewallEntryRule::isValid('::1/128'));
    }

    public function test_ipv4_range_is_valid(): void
    {
        $this->assertTrue(FirewallEntryRule::isValid('10.0.0.1-10.0.0.255'));
        $this->assertTrue(FirewallEntryRule::isValid('192.168.1.1-192.168.1.100'));
    }

    public function test_country_code_is_valid(): void
    {
        $this->assertTrue(FirewallEntryRule::isValid('country:US'));
        $this->assertTrue(FirewallEntryRule::isValid('country:nl'));
        $this->assertTrue(FirewallEntryRule::isValid('country:DE'));
    }

    public function test_host_pattern_is_valid(): void
    {
        $this->assertTrue(FirewallEntryRule::isValid('host:example.com'));
        $this->assertTrue(FirewallEntryRule::isValid('host:sub.domain.example.com'));
        $this->assertTrue(FirewallEntryRule::isValid('host:localhost'));
    }

    public function test_empty_string_is_invalid(): void
    {
        $this->assertFalse(FirewallEntryRule::isValid(''));
        $this->assertFalse(FirewallEntryRule::isValid('   '));
    }

    public function test_file_paths_are_rejected(): void
    {
        $this->assertFalse(FirewallEntryRule::isValid('/etc/passwd'));
        $this->assertFalse(FirewallEntryRule::isValid('/var/www/ips.txt'));
        $this->assertFalse(FirewallEntryRule::isValid('var/www/ips.txt'));
        $this->assertFalse(FirewallEntryRule::isValid('./relative/path'));
        $this->assertFalse(FirewallEntryRule::isValid('../traversal'));
        $this->assertFalse(FirewallEntryRule::isValid('..\\traversal'));
    }

    public function test_windows_paths_are_rejected(): void
    {
        $this->assertFalse(FirewallEntryRule::isValid('C:\\Windows\\System32'));
        $this->assertFalse(FirewallEntryRule::isValid('C:/Users/file.txt'));
    }

    public function test_invalid_country_code_is_rejected(): void
    {
        $this->assertFalse(FirewallEntryRule::isValid('country:'));
        $this->assertFalse(FirewallEntryRule::isValid('country:USA'));
        $this->assertFalse(FirewallEntryRule::isValid('country:1'));
    }

    public function test_invalid_host_is_rejected(): void
    {
        $this->assertFalse(FirewallEntryRule::isValid('host:'));
        $this->assertFalse(FirewallEntryRule::isValid('host:-invalid.com'));
    }

    public function test_invalid_cidr_prefix_is_rejected(): void
    {
        $this->assertFalse(FirewallEntryRule::isValid('10.0.0.0/33'));
        $this->assertFalse(FirewallEntryRule::isValid('::1/129'));
    }

    public function test_garbage_input_is_rejected(): void
    {
        $this->assertFalse(FirewallEntryRule::isValid('not-an-ip'));
        $this->assertFalse(FirewallEntryRule::isValid('foo bar'));
        $this->assertFalse(FirewallEntryRule::isValid('999.999.999.999'));
    }

    public function test_invalid_range_is_rejected(): void
    {
        $this->assertFalse(FirewallEntryRule::isValid('10.0.0.1-abc'));
        $this->assertFalse(FirewallEntryRule::isValid('abc-10.0.0.1'));
    }

    public function test_whitespace_is_trimmed(): void
    {
        $this->assertTrue(FirewallEntryRule::isValid(' 10.0.0.1 '));
        $this->assertTrue(FirewallEntryRule::isValid('  country:US  '));
    }
}
