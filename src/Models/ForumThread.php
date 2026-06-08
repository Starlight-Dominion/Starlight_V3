<?php
declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumThread extends Model
{
    protected $table = 'forum_threads';

    protected $casts = [
        'is_stickied' => 'boolean',
        'is_locked' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'alliance_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'thread_id')->orderBy('created_at', 'ASC');
    }
}
