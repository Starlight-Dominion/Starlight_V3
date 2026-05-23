<?php
declare(strict_types=1);

namespace sdo\Models;

class ApiApplication extends Model
{
    protected $table = 'api_applications';

    public $timestamps = true;

    protected $casts = [
        'user_id' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
