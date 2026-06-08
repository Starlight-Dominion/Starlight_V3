<?php
declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllianceBankLog extends Model
{
    protected $table = 'alliance_bank_logs';
    public $timestamps = false;

    protected $casts = [
        'amount' => 'integer',
        'created_at' => 'datetime'
    ];

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'alliance_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
