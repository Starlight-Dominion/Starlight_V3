<?php
declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use sdo\Models\ForumThread;
use sdo\Models\ForumPost;
use Illuminate\Support\Collection;

interface ForumRepositoryInterface
{
    public function getThreads(int $allianceId): Collection;
    public function findThreadById(int $id): ?ForumThread;
    public function createThread(array $data): ForumThread;
    public function updateThread(int $id, array $data): bool;
    public function deleteThread(int $id): bool;

    public function getPosts(int $threadId): Collection;
    public function createPost(array $data): ForumPost;
    public function updatePost(int $id, array $data): bool;
    public function deletePost(int $id): bool;
}
