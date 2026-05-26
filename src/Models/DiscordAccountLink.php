<?php
declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
