<?php
declare(strict_types=1);

namespace sdo\Models;

class DiscordAccountLink extends Model
{
    protected $table = 'discord_account_links';

    public $timestamps = true;

    protected $casts = [
        'user_id' => 'integer',
        'is_active' => 'boolean',
        'linked_at' => 'datetime',
        'unlinked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
