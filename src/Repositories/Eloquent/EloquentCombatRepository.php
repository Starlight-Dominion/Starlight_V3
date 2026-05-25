<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use Illuminate\Database\Capsule\Manager as Capsule;
use sdo\Repositories\Interfaces\CombatRepositoryInterface;

class EloquentCombatRepository implements CombatRepositoryInterface
{
    public function logBattle(array $data): int
    {
        $outcome = ($data['outcome'] ?? $data['result'] ?? 'defeat') === 'victory' ? 'victory' : 'defeat';

        return Capsule::table('battle_logs')->insertGetId([
            'attacker_id' => (int)$data['attacker_id'],
            'defender_id' => (int)$data['defender_id'],
            'attacker_name' => (string)($data['attacker_name'] ?? 'Unknown Attacker'),
            'defender_name' => (string)($data['defender_name'] ?? 'Unknown Defender'),
            'outcome' => $outcome,
            'credits_stolen' => (int)($data['credits_stolen'] ?? $data['gold_looted'] ?? 0),
            'turns_used' => (int)($data['turns_used'] ?? $data['turns_spent'] ?? 0),
            'attacker_damage' => (int)($data['attacker_damage'] ?? 0),
            'defender_damage' => (int)($data['defender_damage'] ?? 0),
            'attacker_xp_gained' => (int)($data['attacker_xp_gained'] ?? 0),
            'defender_xp_gained' => (int)($data['defender_xp_gained'] ?? 0),
            'guards_lost' => (int)($data['guards_lost'] ?? 0),
            'attacker_soldiers_lost' => (int)($data['attacker_soldiers_lost'] ?? 0),
            'structure_damage' => (int)($data['structure_damage'] ?? 0),
            'loot_factor' => (float)($data['loot_factor'] ?? 1.0),
            'battle_time' => (string)($data['battle_time'] ?? date('Y-m-d H:i:s'))
        ]);
    }

    public function getLogsByKingdom(int $kingdomId, int $limit = 10): array
    {
        return Capsule::table('battle_logs')
            ->where('attacker_id', $kingdomId)
            ->orWhere('defender_id', $kingdomId)
            ->orderBy('battle_time', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function findLogById(int $id): ?object
    {
        return Capsule::table('battle_logs')->find($id);
    }
}