<?php

namespace Magentron\LaravelFirewallFilament\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'firewall_filament_audit';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ability',
        'action',
        'target',
        'before',
        'after',
        'created_at',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'created_at' => 'datetime',
    ];
}
