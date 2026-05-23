<?php
declare(strict_types=1);

namespace sdo\Models;

class DominionManpower extends Model
{
    protected $table = 'dominion_manpower';

    public $timestamps = false;

    protected $primaryKey = null;
    public $incrementing = false;

    protected $casts = [
        'dominion_id' => 'integer',
        'unit_id' => 'integer',
        'total_quantity' => 'integer',
        'stabled_quantity' => 'integer'
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function dominion()
    {
        return $this->belongsTo(Dominion::class, 'dominion_id');
    }
}
