<?php
declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alliance extends Model
{
    protected $table = 'alliances';
    public $timestamps = false;

    protected $casts = [
        'bank_credits' => 'integer',
        'war_prestige' => 'integer',
        'is_joinable' => 'boolean',
        'created_at' => 'datetime'
    ];

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'alliance_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(AllianceRole::class, 'alliance_id')->orderBy('order', 'ASC');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(AllianceApplication::class, 'alliance_id');
    }

    public function structures(): HasMany
    {
        return $this->hasMany(AllianceStructure::class, 'alliance_id');
    }
}
