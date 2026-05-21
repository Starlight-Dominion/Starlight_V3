<?php
declare(strict_types=1);

namespace sdo\Models;

class Dominion extends Model
{
    protected $table = 'dominions';

    /**
     * Standard timestamps are enabled.
     * Eloquent expects 'created_at' and 'updated_at' columns.
     */
    public $timestamps = true;

    protected $casts = [
        'credits' => 'integer',
        'credits_banked' => 'integer',
        'citizens' => 'integer',
        'turns' => 'integer',
        'xp' => 'integer',
        'foundation_hp' => 'integer',
        'foundation_max_hp' => 'integer',
        'deposits_today' => 'integer',
        'last_deposit_timestamp' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_tick' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function race()
    {
        return $this->belongsTo(Race::class, 'race_id');
    }

    public function getPlayerLevel(): int
    {
        return (int)floor(sqrt($this->xp / 100)) + 1;
    }
}