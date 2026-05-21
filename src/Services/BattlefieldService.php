<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Repositories\Interfaces\CombatRepositoryInterface;
use sdo\Repositories\Interfaces\KingdomRepositoryInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class BattlefieldService
{
    public function __construct(
        private CombatRepositoryInterface $combatRepository,
        private KingdomRepositoryInterface $kingdomRepository,
        private TacticalService $tacticalService
    ) {}

    public function getBattlefieldList(): array
    {
        // Map kingdoms with their total manpower
        return Capsule::table('dominions')
            ->join('users', 'dominions.user_id', '=', 'users.id')
            ->select('dominions.id', 'dominions.name', 'users.username', 'dominions.credits', 'dominions.xp')
            ->get()
            ->map(fn($d) => [
                'kingdom_id' => $d->id,
                'name' => $d->name,
                'username' => $d->username,
                'gold' => $d->credits,
                'level' => floor(sqrt($d->xp / 100)) + 1
            ])->toArray();
    }

    public function executeAttack(int $attackerId, int $targetId, int $turns): array
    {
        return Capsule::transaction(function() use ($attackerId, $targetId, $turns) {
            $attacker = Capsule::table('dominions')->where('id', $attackerId)->lockForUpdate()->first();
            $target = Capsule::table('dominions')->where('id', $targetId)->lockForUpdate()->first();

            if ($attacker->turns < $turns) throw new Exception("Insufficient turns.");

            $attackerRatings = $this->tacticalService->calculateTacticalRatings($attackerId);
            $targetRatings = $this->tacticalService->calculateTacticalRatings($targetId);

            if ($attackerRatings['total_units'] <= 0) throw new Exception("You have no army to lead.");

            $result = $this->simulateBattle($attackerRatings, $targetRatings);

            // Deduct Turns
            Capsule::table('dominions')->where('id', $attackerId)->decrement('turns', $turns);

            // Apply Casualties
            $this->applyLosses($attackerId, (float)$result['attacker_loss_percent']);
            $this->applyLosses($targetId, (float)$result['defender_loss_percent']);

            $goldLooted = 0;
            if ($result['victor'] === 'attacker') {
                $lootMult = min(0.1 * $turns, 0.5);
                $goldLooted = (int)($target->credits * $lootMult);
                
                Capsule::table('dominions')->where('id', $targetId)->decrement('credits', $goldLooted);
                Capsule::table('dominions')->where('id', $attackerId)->increment('credits', $goldLooted);
                Capsule::table('dominions')->where('id', $attackerId)->increment('xp', 5 * $turns);
            }

            return [
                'success' => true,
                'victor' => $result['victor'],
                'loot' => $goldLooted,
                'message' => $result['victor'] === 'attacker' ? "Victory! Looted {$goldLooted} CP." : "Defeat! Forces repelled."
            ];
        });
    }

    private function applyLosses(int $dominionId, float $percent): void
    {
        if ($percent <= 0) return;
        $factor = $percent / 100;

        $manpower = Capsule::table('dominion_manpower')->where('dominion_id', $dominionId)->get();
        foreach ($manpower as $m) {
            $loss = (int)ceil($m->total_quantity * $factor);
            if ($loss > 0) {
                Capsule::table('dominion_manpower')
                    ->where('dominion_id', $dominionId)
                    ->where('unit_id', $m->unit_id)
                    ->decrement('total_quantity', $loss);
            }
        }
    }

    private function simulateBattle(array $atk, array $def): array
    {
        $aP = $atk['weighted_power'];
        $dP = $def['weighted_power'];
        if ($aP <= 0) return ['victor' => 'defender', 'attacker_loss_percent' => 100, 'defender_loss_percent' => 0];
        if ($dP <= 0) return ['victor' => 'attacker', 'attacker_loss_percent' => 2, 'defender_loss_percent' => 100];

        $ratio = $aP / $dP;
        if ($ratio > 1.2) return ['victor' => 'attacker', 'attacker_loss_percent' => 5, 'defender_loss_percent' => 40];
        return ['victor' => 'defender', 'attacker_loss_percent' => 30, 'defender_loss_percent' => 10];
    }
}