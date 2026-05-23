<?php
declare(strict_types=1);

namespace sdo\Models;

class Structure extends Model
{
    protected $table = 'structures';

    public $timestamps = false;

    protected $casts = [
        'max_level' => 'integer'
    ];

    public function levels()
    {
        return $this->hasMany(StructureLevel::class, 'structure_id');
    }
}
