<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\RecruitmentSession;
use sdo\Repositories\Interfaces\RecruitmentRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentRecruitmentRepository implements RecruitmentRepositoryInterface
{
    public function findActiveSession(int $dominionId): ?RecruitmentSession
    {
        return RecruitmentSession::where('dominion_id', $dominionId)
            ->where('is_active', true)
            ->first();
    }

    public function lockActiveSession(int $id, int $dominionId): ?RecruitmentSession
    {
        return RecruitmentSession::where('id', $id)
            ->where('dominion_id', $dominionId)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();
    }

    public function countRecentSessions(int $dominionId, int $hours): int
    {
        return RecruitmentSession::where('dominion_id', $dominionId)
            ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime("-$hours hours")))
            ->count();
    }

    public function createSession(array $data): RecruitmentSession
    {
        return RecruitmentSession::create($data);
    }

    public function updateSession(int $id, array $data): bool
    {
        $session = RecruitmentSession::find($id);
        return $session ? $session->update($data) : false;
    }

    public function incrementClicks(int $id): int
    {
        $session = RecruitmentSession::find($id);
        if (!$session) return 0;

        $session->increment('clicks_count');
        return (int)$session->clicks_count;
    }

    public function getTotalCitizensRecruited(int $dominionId): int
    {
        return (int)RecruitmentSession::where('dominion_id', $dominionId)->sum('clicks_count');
    }

    public function getTodayCitizensRecruited(int $dominionId): int
    {
        return (int)RecruitmentSession::where('dominion_id', $dominionId)
            ->where('created_at', '>=', date('Y-m-d 00:00:00'))
            ->sum('clicks_count');
    }
}
