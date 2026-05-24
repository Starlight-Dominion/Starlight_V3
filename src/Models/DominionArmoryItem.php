<?php
declare(strict_types=1);

namespace sdo\Models;

class DominionArmoryItem extends Model
{
    protected $table = 'kingdom_armory_items';

    public $timestamps = false;

    protected $primaryKey = null;
    public $incrementing = false;

    protected $casts = [
        'kingdom_id' => 'integer',
        'item_id' => 'integer',
        'quantity' => 'integer',
        'is_equipped' => 'boolean'
    ];

    public function item()
    {
        return $this->belongsTo(ArmoryItem::class, 'item_id');
    }

    public function dominion()
    {
        return $this->belongsTo(Dominion::class, 'kingdom_id');
    }
}
