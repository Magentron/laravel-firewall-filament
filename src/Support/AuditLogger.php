<?php

namespace Magentron\LaravelFirewallFilament\Support;

use Magentron\LaravelFirewallFilament\Models\AuditLog;

class AuditLogger
{
    public function log(
        ?int $userId,
        string $ability,
        string $action,
        ?string $target = null,
        mixed $before = null,
        mixed $after = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId,
            'ability' => $ability,
            'action' => $action,
            'target' => $target,
            'before' => $before,
            'after' => $after,
            'created_at' => now(),
        ]);
    }

    public static function currentUserId(): ?int
    {
        try {
            return \Filament\Facades\Filament::auth()->id();
        } catch (\Throwable) {
            return null;
        }
    }
}
