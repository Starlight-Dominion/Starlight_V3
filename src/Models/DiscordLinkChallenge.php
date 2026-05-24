<?php
declare(strict_types=1);

namespace sdo\Models;

class DiscordLinkChallenge extends Model
{
    protected $table = 'discord_link_challenges';

    public $timestamps = true;

    protected $casts = [
        'user_id' => 'integer',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
