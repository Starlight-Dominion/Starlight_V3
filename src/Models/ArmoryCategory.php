<?php
declare(strict_types=1);

namespace sdo\Models;

class ArmoryCategory extends Model
{
    protected $table = 'armory_categories';

    public $timestamps = false;

    protected $casts = [
        'unit_type_id' => 'integer',
        'slots' => 'integer'
    ];

    public function unitType()
    {
        return $this->belongsTo(ArmoryUnitType::class, 'unit_type_id');
    }

    public function items()
    {
        return $this->hasMany(ArmoryItem::class, 'category_id');
    }
}
