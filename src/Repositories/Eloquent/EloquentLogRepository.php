<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\GameLog;
use sdo\Repositories\Interfaces\LogRepositoryInterface;

class EloquentLogRepository implements LogRepositoryInterface
{
    public function log(array $data): void
    {
        GameLog::create($data);
    }

    public function getAuditLogs(int $limit = 100): array
    {
        return GameLog::where('action', 'LIKE', 'ADMIN_%')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getRecentBattleLogs(int $limit = 50): array
    {
        return \sdo\Models\BattleLog::orderBy('battle_time', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
