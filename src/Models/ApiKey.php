<?php
declare(strict_types=1);

namespace sdo\Models;

class ApiKey extends Model
{
    protected $table = 'api_keys';

    protected $fillable = [
        'user_id',
        'api_token',
        'rate_limit_per_minute',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rate_limit_per_minute' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function logs()
    {
        return $this->hasMany(ApiLog::class, 'api_key_id');
    }
}
