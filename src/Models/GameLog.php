<?php
declare(strict_types=1);

namespace sdo\Models;

class GameLog extends Model
{
    protected $table = 'game_logs';

    /**
     * Disable automatic Eloquent timestamps.
     * The table only has created_at.
     */
    public $timestamps = false;

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
