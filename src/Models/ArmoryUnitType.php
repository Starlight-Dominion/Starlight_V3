<?php
declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ArmoryUnitType extends Model
{
    protected $table = 'armory_unit_types';

    public $timestamps = false;

    public function categories(): HasMany
    {
        return $this->hasMany(ArmoryCategory::class, 'unit_type_id');
    }
}
