<?php
declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllianceRole extends Model
{
    protected $table = 'alliance_roles';
    public $timestamps = false;

    protected $casts = [
        'order' => 'integer',
        'can_invite' => 'boolean',
        'can_kick' => 'boolean',
        'can_manage_roles' => 'boolean',
        'can_moderate_forum' => 'boolean',
        'can_bank_withdraw' => 'boolean',
        'can_purchase_structures' => 'boolean'
    ];

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'alliance_id');
    }
}
