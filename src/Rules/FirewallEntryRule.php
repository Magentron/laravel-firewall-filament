<?php

namespace Magentron\LaravelFirewallFilament\Rules;

class FirewallEntryRule
{
    public static function isValid(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        if (str_starts_with($value, '/') || str_starts_with($value, '.') || str_contains($value, '..')) {
            return false;
        }

        if (preg_match('#^[a-zA-Z]:[/\\\\]#', $value) || str_contains($value, '\\')) {
            return false;
        }

        if (preg_match('/^country:([a-zA-Z]{2})$/', $value)) {
            return true;
        }

        if (preg_match('/^host:([a-zA-Z0-9]([a-zA-Z0-9\-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]*[a-zA-Z0-9])?)*)$/', $value)) {
            return true;
        }

        if (filter_var($value, FILTER_VALIDATE_IP)) {
            return true;
        }

        if (preg_match('#^(\d{1,3}\.){3}\d{1,3}/\d{1,2}$#', $value)) {
            $parts = explode('/', $value);
            if (filter_var($parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $prefix = (int) $parts[1];
                return $prefix >= 0 && $prefix <= 32;
            }
        }

        if (preg_match('#^[0-9a-fA-F:]+/\d{1,3}$#', $value)) {
            $parts = explode('/', $value);
            if (filter_var($parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $prefix = (int) $parts[1];
                return $prefix >= 0 && $prefix <= 128;
            }
        }

        if (preg_match('/^(\d{1,3}\.){3}\d{1,3}-(\d{1,3}\.){3}\d{1,3}$/', $value)) {
            $ips = explode('-', $value);
            return filter_var($ips[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                && filter_var($ips[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        }

        return false;
    }
}
