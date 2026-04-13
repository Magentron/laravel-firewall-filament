<?php

namespace Magentron\LaravelFirewallFilament\Support;

class LockoutDetector
{
    public static function wouldLockOut(string $targetIp, ?string $requestIp): bool
    {
        if ($requestIp === null || $requestIp === '') {
            return false;
        }

        return self::ipMatchesTarget($requestIp, $targetIp);
    }

    private static function ipMatchesTarget(string $requestIp, string $target): bool
    {
        if (strcasecmp($requestIp, $target) === 0) {
            return true;
        }

        if (str_contains($target, '/')) {
            return self::ipInCidr($requestIp, $target);
        }

        if (str_contains($target, '-')) {
            return self::ipInRange($requestIp, $target);
        }

        return false;
    }

    private static function ipInCidr(string $ip, string $cidr): bool
    {
        $parts = explode('/', $cidr, 2);
        if (count($parts) !== 2) {
            return false;
        }

        $subnet = @inet_pton($parts[0]);
        $ipBin = @inet_pton($ip);

        if ($subnet === false || $ipBin === false) {
            return false;
        }

        if (strlen($subnet) !== strlen($ipBin)) {
            return false;
        }

        $bits = (int) $parts[1];
        $totalBits = strlen($subnet) * 8;

        if ($bits < 0 || $bits > $totalBits) {
            return false;
        }

        $mask = str_repeat("\xff", (int) ($bits / 8));
        if ($bits % 8 !== 0) {
            $mask .= chr(0xff << (8 - ($bits % 8)) & 0xff);
        }
        $mask = str_pad($mask, strlen($subnet), "\x00");

        return ($ipBin & $mask) === ($subnet & $mask);
    }

    private static function ipInRange(string $ip, string $range): bool
    {
        $parts = explode('-', $range, 2);
        if (count($parts) !== 2) {
            return false;
        }

        $low = @inet_pton(trim($parts[0]));
        $high = @inet_pton(trim($parts[1]));
        $ipBin = @inet_pton($ip);

        if ($low === false || $high === false || $ipBin === false) {
            return false;
        }

        if (strlen($low) !== strlen($ipBin) || strlen($high) !== strlen($ipBin)) {
            return false;
        }

        return $ipBin >= $low && $ipBin <= $high;
    }
}
