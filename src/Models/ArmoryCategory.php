<?php
declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArmoryCategory extends Model
{
    protected $table = 'armory_categories';

    public $timestamps = false;

    protected $casts = [
        'unit_type_id' => 'integer',
        'slots' => 'integer'
    ];

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(ArmoryUnitType::class, 'unit_type_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ArmoryItem::class, 'category_id');
    }
}
