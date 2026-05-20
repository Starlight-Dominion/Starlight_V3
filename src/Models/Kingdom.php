<?php
namespace sdo\Models;

class Kingdom extends Model
{
    protected $table = 'kingdoms';

    protected $casts = [
        'gold' => 'integer',
        'citizens' => 'integer',
        'turns' => 'integer',
        'miners' => 'integer',
        'xp' => 'integer',
        'last_tick' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // NEW RELATIONSHIP
    public function race()
    {
        return $this->belongsTo(Race::class, 'race_id');
    }

    public function getPlayerLevel(): int
    {
        return floor(sqrt($this->xp / 100)) + 1;
    }
}