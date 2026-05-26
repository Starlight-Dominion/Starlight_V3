<?php
declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Structure extends Model
{
    protected $table = 'structures';

    public $timestamps = false;

    protected $casts = [
        'max_level' => 'integer'
    ];

    public function levels(): HasMany
    {
        return $this->hasMany(StructureLevel::class, 'structure_id');
    }
}
