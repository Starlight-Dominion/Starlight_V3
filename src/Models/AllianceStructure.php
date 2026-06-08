<?php
declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllianceStructure extends Model
{
    protected $table = 'alliance_structures';
    public $timestamps = false;

    protected $casts = [
        'level' => 'integer'
    ];

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'alliance_id');
    }
}
