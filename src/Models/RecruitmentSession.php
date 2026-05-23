<?php
declare(strict_types=1);

namespace sdo\Models;

class RecruitmentSession extends Model
{
    protected $table = 'recruitment_sessions';

    public $timestamps = true;

    protected $casts = [
        'user_id' => 'integer',
        'clicks' => 'integer',
        'completed' => 'boolean',
        'expires_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
