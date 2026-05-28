<?php
declare(strict_types=1);

namespace sdo\Models;

class TickLog extends Model
{
    protected $table = 'tick_logs';

    public $timestamps = false;

    protected $fillable = [
        'tick_time',
        'total_sectors',
        'total_credits_granted',
        'total_citizens_born',
        'total_turns_granted',
        'execution_time_ms',
        'metadata'
    ];

    protected $casts = [
        'tick_time' => 'datetime',
        'metadata' => 'array',
        'execution_time_ms' => 'float'
    ];
}
