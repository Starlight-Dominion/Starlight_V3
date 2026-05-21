<?php
declare(strict_types=1);

namespace sdo\Models;

class GameLog extends Model
{
    protected $table = 'game_logs';

    protected $fillable = [
        'dominion_id',
        'action',
        'description',
        'amount',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'integer'
    ];
}
