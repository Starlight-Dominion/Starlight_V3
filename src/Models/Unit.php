<?php
declare(strict_types=1);

namespace sdo\Models;

class Unit extends Model
{
    protected $table = 'units';

    public $timestamps = false;

    protected $casts = [
        'cost_credits' => 'integer',
        'cost_citizens' => 'integer',
        'cost_turns' => 'integer',
        'power_offense' => 'integer',
        'power_defense' => 'integer',
        'production_credits' => 'integer'
    ];
}
