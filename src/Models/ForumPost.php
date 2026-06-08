<?php
declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumPost extends Model
{
    protected $table = 'forum_posts';

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
