<?php
declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BattleLog extends Model
{
    protected $table = 'battle_logs';

    public $timestamps = false;

    protected $casts = [
        'attacker_id' => 'integer',
        'defender_id' => 'integer',
        'credits_stolen' => 'integer',
        'turns_used' => 'integer',
        'attacker_damage' => 'integer',
        'defender_damage' => 'integer',
        'attacker_xp_gained' => 'integer',
        'defender_xp_gained' => 'integer',
        'guards_lost' => 'integer',
        'attacker_soldiers_lost' => 'integer',
        'structure_damage' => 'integer',
        'loot_factor' => 'float',
        'battle_time' => 'datetime'
    ];

    public function attacker(): BelongsTo
    {
        return $this->belongsTo(Dominion::class, 'attacker_id');
    }

    public function defender(): BelongsTo
    {
        return $this->belongsTo(Dominion::class, 'defender_id');
    }
}
