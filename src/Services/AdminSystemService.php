<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\User;
use sdo\Models\Dominion;
use sdo\Models\BattleLog;
use sdo\Models\GameLog;
use DateTime;
use DateTimeZone;

class AdminSystemService
{
    public function getSystemStats(): array
    {
        $playerCount = User::count();
        $kingdomCount = Dominion::count();
        $totalCredits = (float)Dominion::sum('credits');
        $totalCitizens = (float)Dominion::sum('citizens');
        $totalManpower = (float)\sdo\Models\DominionManpower::sum('total_quantity');
        
        return [
            'total_users' => $playerCount,
            'total_kingdoms' => $kingdomCount,
            'total_credits' => $totalCredits,
            'total_citizens' => $totalCitizens,
            'total_manpower' => $totalManpower,
            'server_time' => (new DateTime('now', new DateTimeZone('America/New_York')))->format('H:i:s T')
        ];
    }

    public function logAdminAction(int $adminId, string $action, string $description, array $metadata = []): void
    {
        GameLog::create([
            'dominion_id' => $adminId,
            'action' => 'ADMIN_' . strtoupper($action),
            'description' => $description,
            'metadata' => $metadata
        ]);
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
        return BattleLog::orderBy('battle_time', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
