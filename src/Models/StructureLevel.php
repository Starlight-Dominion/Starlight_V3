<?php
declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StructureLevel extends Model
{
    protected $table = 'structure_levels';

    public $timestamps = false;

    // Phinx migration uses composite primary key [structure_id, level]
    // Eloquent doesn't support composite keys well without extra traits, 
    // but for read-only lookups in service logic, we define the relationships.
    protected $primaryKey = null;
    public $incrementing = false;

    protected $casts = [
        'structure_id' => 'integer',
        'level' => 'integer',
        'cost' => 'integer',
        'buff_hp' => 'integer',
        'buff_economy' => 'integer',
        'buff_offense' => 'integer',
        'buff_defense' => 'integer',
        'capacity' => 'integer',
        'player_level_req' => 'integer',
        'buff_citizens_per_tick' => 'integer',
        'buff_unit_guards' => 'integer',
        'buff_unit_soldiers' => 'integer',
        'buff_unit_spies' => 'integer',
        'buff_unit_sentries' => 'integer'
    ];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'structure_id');
    }
}
