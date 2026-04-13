<?php

namespace Magentron\LaravelFirewallFilament\Support;

class LogEntryParser
{
    public static function parseTimestamp(string $line): ?string
    {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[^\]]*)\]/', $line, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
