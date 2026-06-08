<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use sdo\Models\RecruitmentSession;
use Illuminate\Support\Collection;

interface RecruitmentRepositoryInterface
{
    public function findActiveSession(int $dominionId): ?RecruitmentSession;
    public function lockActiveSession(int $id, int $dominionId): ?RecruitmentSession;
    public function countRecentSessions(int $dominionId, int $hours): int;
    public function createSession(array $data): RecruitmentSession;
    public function updateSession(int $id, array $data): bool;
    public function incrementClicks(int $id): int;
    public function getTotalCitizensRecruited(int $dominionId): int;
    public function getTodayCitizensRecruited(int $dominionId): int;
}
