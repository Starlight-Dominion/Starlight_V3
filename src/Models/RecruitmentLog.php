<?php

declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentLog extends Model
{
    protected $table = 'recruitment_logs';

    public $timestamps = false;

    protected $fillable = [
        'dominion_id',
        'action',
        'description',
        'amount'
    ];

    protected $casts = [
        'dominion_id' => 'integer',
        'amount' => 'integer'
    ];

    public function dominion(): BelongsTo
    {
        return $this->belongsTo(Dominion::class, 'dominion_id');
    }
}
