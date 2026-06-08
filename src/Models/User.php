<?php
declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Model
{
    protected $table = 'users';
    
    public $timestamps = true;

    protected $hidden = ['password'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_bot' => 'boolean',
        'is_admin' => 'boolean',
        'stasis_until' => 'datetime',
        'handle_last_changed' => 'datetime',
        'bot_profile_id' => 'integer',
        'last_bot_action_at' => 'datetime',
        'alliance_id' => 'integer',
        'alliance_role_id' => 'integer'
    ];

    public function alliance(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'alliance_id');
    }

    public function allianceRole(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AllianceRole::class, 'alliance_role_id');
    }

    public function dominion(): HasOne
    {
        return $this->hasOne(Dominion::class, 'user_id');
    }

    public function botProfile(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BotProfile::class, 'bot_profile_id');
    }

    public function setPasswordAttribute(string $password): void
    {
        $this->attributes['password'] = password_hash($password, PASSWORD_DEFAULT);
    }
}