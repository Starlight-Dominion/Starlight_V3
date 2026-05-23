<?php
declare(strict_types=1);

namespace sdo\Models;

class DominionStructure extends Model
{
    protected $table = 'dominion_structures';

    public $timestamps = false;

    protected $primaryKey = null;
    public $incrementing = false;

    protected $casts = [
        'dominion_id' => 'integer',
        'structure_id' => 'integer',
        'level' => 'integer'
    ];

    public function dominion()
    {
        return $this->belongsTo(Dominion::class, 'dominion_id');
    }

    public function structure()
    {
        return $this->belongsTo(Structure::class, 'structure_id');
    }

    /**
     * Get the specific level data for this dominion's structure.
     */
    public function levelData()
    {
        return $this->hasOne(StructureLevel::class, 'structure_id', 'structure_id')
            ->where('level', $this->level);
    }
}
