<?php
declare(strict_types=1);

namespace sdo\Models;

class ApiLog extends Model
{
    protected $table = 'api_logs';

    public $timestamps = false;

    protected $fillable = [
        'api_key_id',
        'endpoint',
        'method',
        'ip_address',
        'status_code',
        'response_time_ms',
        'created_at'
    ];

    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class, 'api_key_id');
    }
}
