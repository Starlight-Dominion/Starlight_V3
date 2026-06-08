<?php
declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\ForumThread;
use sdo\Models\ForumPost;
use sdo\Repositories\Interfaces\ForumRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentForumRepository implements ForumRepositoryInterface
{
    public function getThreads(int $allianceId): Collection
    {
        return ForumThread::where('alliance_id', $allianceId)
            ->with(['user'])
            ->withCount('posts')
            ->orderBy('is_stickied', 'DESC')
            ->orderBy('updated_at', 'DESC')
            ->get();
    }

    public function findThreadById(int $id): ?ForumThread
    {
        return ForumThread::with(['user', 'posts.user'])->find($id);
    }

    public function createThread(array $data): ForumThread
    {
        return ForumThread::create($data);
    }

    public function updateThread(int $id, array $data): bool
    {
        $thread = ForumThread::find($id);
        return $thread ? $thread->update($data) : false;
    }

    public function deleteThread(int $id): bool
    {
        $thread = ForumThread::find($id);
        return $thread ? $thread->delete() : false;
    }

    public function getPosts(int $threadId): Collection
    {
        return ForumPost::where('thread_id', $threadId)->with('user')->orderBy('created_at', 'ASC')->get();
    }

    public function createPost(array $data): ForumPost
    {
        return ForumPost::create($data);
    }

    public function updatePost(int $id, array $data): bool
    {
        $post = ForumPost::find($id);
        return $post ? $post->update($data) : false;
    }

    public function deletePost(int $id): bool
    {
        $post = ForumPost::find($id);
        return $post ? $post->delete() : false;
    }
}
