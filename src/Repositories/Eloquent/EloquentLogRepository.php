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
        $logs = \sdo\Models\BattleLog::with(['attacker', 'defender'])
            ->orderBy('battle_time', 'desc')
            ->limit($limit)
            ->get();

        return $logs->map(function ($log) {
            return [
                'created_at' => $log->battle_time->format('Y-m-d H:i:s'),
                'attacker_name' => $log->attacker ? $log->attacker->name : 'Unknown',
                'defender_name' => $log->defender ? $log->defender->name : 'Unknown',
                'result' => $log->credits_stolen > 0 ? 'attacker' : 'defender',
                'gold_looted' => $log->credits_stolen
            ];
        })->toArray();
    }
}
