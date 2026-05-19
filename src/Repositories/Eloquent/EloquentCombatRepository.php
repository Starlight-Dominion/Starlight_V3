<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use Illuminate\Database\Capsule\Manager as Capsule;
use sdo\Repositories\Interfaces\CombatRepositoryInterface;

class EloquentCombatRepository implements CombatRepositoryInterface
{
    public function logBattle(array $data): int
    {
        return Capsule::table('battle_logs')->insertGetId([
            'attacker_kingdom_id' => $data['attacker_id'],
            'defender_kingdom_id' => $data['defender_id'],
            'attacker_units' => json_encode($data['attacker_units']),
            'defender_units' => json_encode($data['defender_units']),
            'result' => $data['result'],
            'attacker_loss_percent' => $data['attacker_loss_percent'],
            'defender_loss_percent' => $data['defender_loss_percent'],
            'gold_looted' => $data['gold_looted'],
            'turns_spent' => $data['turns_spent'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getLogsByKingdom(int $kingdomId, int $limit = 10): array
    {
        return Capsule::table('battle_logs')
            ->where('attacker_kingdom_id', $kingdomId)
            ->orWhere('defender_kingdom_id', $kingdomId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function findLogById(int $id): ?object
    {
        return Capsule::table('battle_logs')->find($id);
    }
}