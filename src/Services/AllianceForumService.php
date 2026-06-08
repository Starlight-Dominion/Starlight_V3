<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\User;
use sdo\Repositories\Interfaces\ForumRepositoryInterface;
use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Infrastructure\TransactionManager;
use Exception;

class AllianceForumService
{
    public function __construct(
        private ForumRepositoryInterface $forumRepository,
        private UserRepositoryInterface $userRepository,
        private TransactionManager $transactionManager
    ) {}

    public function getThreads(int $allianceId): array
    {
        return $this->forumRepository->getThreads($allianceId)->toArray();
    }

    public function getThread(int $threadId, int $allianceId): array
    {
        $thread = $this->forumRepository->findThreadById($threadId);
        if (!$thread || $thread->alliance_id !== $allianceId) {
            throw new Exception("Thread not found.");
        }
        return $thread->toArray();
    }

    public function createThread(int $userId, int $allianceId, string $title, string $content): int
    {
        if (empty($title) || empty($content)) throw new Exception("Title and content required.");

        return $this->transactionManager->transaction(function () use ($userId, $allianceId, $title, $content) {
            $thread = $this->forumRepository->createThread([
                'alliance_id' => $allianceId,
                'user_id' => $userId,
                'title' => $title
            ]);

            $this->forumRepository->createPost([
                'thread_id' => $thread->id,
                'user_id' => $userId,
                'content' => $content
            ]);

            return $thread->id;
        });
    }

    public function createPost(int $userId, int $threadId, int $allianceId, string $content): void
    {
        if (empty($content)) throw new Exception("Content required.");

        $this->transactionManager->transaction(function () use ($userId, $threadId, $allianceId, $content) {
            $thread = $this->forumRepository->findThreadById($threadId);
            if (!$thread || $thread->alliance_id !== $allianceId) throw new Exception("Thread not found.");
            if ($thread->is_locked) throw new Exception("Thread is locked.");

            $this->forumRepository->createPost([
                'thread_id' => $threadId,
                'user_id' => $userId,
                'content' => $content
            ]);

            $thread->touch(); // Update last_post_at via updated_at
        });
    }

    public function moderateThread(int $userId, int $threadId, int $allianceId, string $action): void
    {
        $user = $this->userRepository->findById($userId);
        if (!$user->allianceRole || !$user->allianceRole->can_moderate_forum) {
            throw new Exception("Permission denied.");
        }

        $thread = $this->forumRepository->findThreadById($threadId);
        if (!$thread || $thread->alliance_id !== $allianceId) throw new Exception("Thread not found.");

        $data = match ($action) {
            'lock' => ['is_locked' => true],
            'unlock' => ['is_locked' => false],
            'sticky' => ['is_stickied' => true],
            'unsticky' => ['is_stickied' => false],
            default => throw new Exception("Invalid action.")
        };

        $this->forumRepository->updateThread($threadId, $data);
    }
}
