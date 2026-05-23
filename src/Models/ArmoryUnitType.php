<?php
declare(strict_types=1);

namespace sdo\Models;

class ArmoryUnitType extends Model
{
    protected $table = 'armory_unit_types';

    public $timestamps = false;

    public function categories()
    {
        return $this->hasMany(ArmoryCategory::class, 'unit_type_id');
    }
}
