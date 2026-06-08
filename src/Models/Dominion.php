<?php
declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'strength_points' => 'integer',
        'constitution_points' => 'integer',
        'dexterity_points' => 'integer',
        'charisma_points' => 'integer',
        'deposits_today' => 'integer',
        'miners' => 'integer',
        'current_mine_tier' => 'integer',
        'current_mine_level' => 'integer',
        'housing_level' => 'integer',
        'mercenary_market_level' => 'integer',
        'held_citizens' => 'integer',
        'last_untrained' => 'datetime',
        'last_deposit_timestamp' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_tick' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class, 'race_id');
    }

    public function structures(): HasMany
    {
        return $this->hasMany(DominionStructure::class, 'dominion_id');
    }

    public function manpower(): HasMany
    {
        return $this->hasMany(DominionManpower::class, 'dominion_id');
    }

    public function getPlayerLevel(): int
    {
        return (int)floor(sqrt($this->xp / 100)) + 1;
    }
}