<?php
declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class BotProfile extends Model
{
    protected $table = 'bot_profiles';
    
    public $timestamps = true;

    protected $casts = [
        'action_frequency_minutes' => 'integer',
        'weight_attack' => 'integer',
        'weight_build' => 'integer',
        'weight_train' => 'integer',
        'weight_explore' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'bot_profile_id');
    }
}
