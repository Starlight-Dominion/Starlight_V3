<?php

declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminLog extends Model
{
    protected $table = 'admin_logs';

    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'action',
        'description',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'admin_id' => 'integer'
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
