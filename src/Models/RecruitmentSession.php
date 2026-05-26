<?php
declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentSession extends Model
{
    protected $table = 'recruitment_sessions';

    public $timestamps = true;
    public const UPDATED_AT = null;

    protected $fillable = [
        'dominion_id',
        'clicks_count',
        'is_active',
        'completed_at'
    ];

    protected $casts = [
        'dominion_id' => 'integer',
        'clicks_count' => 'integer',
        'is_active' => 'boolean',
        'completed_at' => 'datetime'
    ];

    public function dominion(): BelongsTo
    {
        return $this->belongsTo(Dominion::class, 'dominion_id');
    }
}
