<?php

namespace Magentron\LaravelFirewallFilament\Resources\AuditLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Magentron\LaravelFirewallFilament\Resources\AuditLogResource;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    public function getTitle(): string
    {
        return 'Audit Trail';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
