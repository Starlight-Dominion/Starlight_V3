<?php
declare(strict_types=1);

namespace sdo\Models;

class ArmoryItem extends Model
{
    protected $table = 'armory_items';

    public $timestamps = false;

    protected $casts = [
        'category_id' => 'integer',
        'attack_bonus' => 'integer',
        'defense_bonus' => 'integer',
        'cost' => 'integer',
        'armory_level_req' => 'integer'
    ];
}
