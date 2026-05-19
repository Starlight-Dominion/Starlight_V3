<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Repositories\Interfaces\CombatRepositoryInterface;
use sdo\Repositories\Interfaces\KingdomRepositoryInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class BattlefieldService
{
    private array $unitsConfig;

    public function __construct(
        private CombatRepositoryInterface $combatRepository,
        private KingdomRepositoryInterface $kingdomRepository,
        private TacticalService $tacticalService
    ) {
        $this->unitsConfig = require __DIR__ . '/../../config/units.php';
    }

    public function getBattlefieldList(): array
    {
        return $this->kingdomRepository->getBattlefieldList()
            ->map(fn($k) => [
                'kingdom_id' => $k->id,
                'name' => $k->kingdom_name,
                'username' => $k->user->username,
                'gold' => $k->gold,
                'level' => floor(sqrt($k->xp / 100)) + 1,
                'fort' => $k->unit_guards + $k->unit_soldiers + $k->unit_spies + $k->unit_sentries
            ])->toArray();
    }

    public function executeAttack(int $attackerId, int $targetId, int $turns): array
    {
        return Capsule::transaction(function() use ($attackerId, $targetId, $turns) {
            $attacker = $this->kingdomRepository->lockForUpdate($attackerId);
            $target = $this->kingdomRepository->lockForUpdate($targetId);

            if (!$attacker || !$target) {
                throw new Exception("Target kingdom has disappeared into the shadows.");
            }

            if ($attacker->turns < $turns) {
                throw new Exception("You do not have enough Turns to launch this assault.");
            }

            $attackerUnits = $this->getUnitArray($attacker);
            $targetUnits = $this->getUnitArray($target);

            if (array_sum($attackerUnits) <= 0) {
                throw new Exception("You cannot attack without an army.");
            }

            $attackerRatings = $this->tacticalService->calculateTacticalRatings($attackerId);
            $targetRatings = $this->tacticalService->calculateTacticalRatings($targetId);

            $result = $this->simulateBattleWithRatings($attackerRatings, $targetRatings);

            // Deduct Turns
            $this->kingdomRepository->decrementStats($attackerId, ['turns' => $turns]);

            // Apply losses
            $this->applyLosses($attackerId, $attackerUnits, (float)$result['attacker_loss_percent']);
            $this->applyLosses($targetId, $targetUnits, (float)$result['defender_loss_percent']);

            $goldLooted = 0;
            if ($result['victor'] === 'attacker') {
                $lootMult = min(0.1 * $turns, 0.5);
                $goldLooted = (int)($target->gold * $lootMult);
                
                $this->kingdomRepository->decrementStats($targetId, ['gold' => $goldLooted]);
                $this->kingdomRepository->incrementStats($attackerId, ['gold' => $goldLooted, 'xp' => 5 * $turns]);
            }

            $battleId = $this->combatRepository->logBattle([
                'attacker_id' => $attackerId,
                'defender_id' => $targetId,
                'attacker_units' => $attackerUnits,
                'defender_units' => $targetUnits,
                'result' => $result['victor'],
                'attacker_loss_percent' => $result['attacker_loss_percent'],
                'defender_loss_percent' => $result['defender_loss_percent'],
                'gold_looted' => $goldLooted,
                'turns_spent' => $turns
            ]);

            return [
                'success' => true,
                'battle_id' => $battleId,
                'victor' => $result['victor'],
                'loot' => $goldLooted,
                'attacker_loss' => $result['attacker_loss_percent'],
                'defender_loss' => $result['defender_loss_percent'],
                'message' => $result['victor'] === 'attacker'
                    ? "Victory! You looted " . number_format($goldLooted) . " gold."
                    : "Defeat! Your army was repelled."
            ];
        });
    }

    private function getUnitArray($k): array
    {
        return [
            'guards' => (int)$k->stabled_unit_guards,
            'soldiers' => (int)$k->stabled_unit_soldiers,
            'spies' => (int)$k->stabled_unit_spies,
            'sentries' => (int)$k->stabled_unit_sentries,
        ];
    }

    private function applyLosses(int $kingdomId, array $units, float $percent): void
    {
        if ($percent <= 0) return;
        $factor = $percent / 100;
        $losses = [];

        foreach ($units as $type => $count) {
            $loss = (int)ceil($count * $factor);
            if ($loss > 0) {
                // Deduct from both total inventory and stabled count
                $losses["unit_" . $type] = $loss;
                $losses["stabled_unit_" . $type] = $loss;
            }
        }

        if (!empty($losses)) {
            $this->kingdomRepository->decrementStats($kingdomId, $losses);
        }
    }

    public function simulateBattleWithRatings(array $atk, array $def): array
    {
        $atkPower = $atk['weighted_power'] ?? 0;
        $defPower = $def['weighted_power'] ?? 0;

        if ($atkPower <= 0) return ['victor' => 'defender', 'attacker_loss_percent' => 100, 'defender_loss_percent' => 0];
        if ($defPower <= 0) return ['victor' => 'attacker', 'attacker_loss_percent' => 2, 'defender_loss_percent' => 100];

        $ratio = $atkPower / $defPower;

        if ($ratio > 1.5) {
            return ['victor' => 'attacker', 'attacker_loss_percent' => 5, 'defender_loss_percent' => 85];
        } elseif ($ratio > 1.0) {
            return ['victor' => 'attacker', 'attacker_loss_percent' => 15, 'defender_loss_percent' => 55];
        } elseif ($ratio > 0.7) {
            return ['victor' => 'defender', 'attacker_loss_percent' => 45, 'defender_loss_percent' => 20];
        } else {
            return ['victor' => 'defender', 'attacker_loss_percent' => 80, 'defender_loss_percent' => 5];
        }
    }

    /** @deprecated Use TacticalService via simulateBattleWithRatings */
    public function simulateBattle(array $atk, array $def): array
    {
        return $this->simulateBattleWithRatings(
            ['weighted_power' => $this->calculatePower($atk)],
            ['weighted_power' => $this->calculatePower($def)]
        );
    }

    /** @deprecated Use TacticalService for power calculations */
    public function calculatePower(array $units): float
    {
        $off = 0; $dfn = 0;
        foreach ($units as $type => $count) {
            $off += $count * ($this->unitsConfig[$type]['power_offense'] ?? 0);
            $dfn += $count * ($this->unitsConfig[$type]['power_defense'] ?? 0);
        }
        return pow($off * 1.1, 0.3) * pow($dfn * 0.9, 0.3);
    }

    public function getBattleLog(int $id): ?object
    {
        $log = $this->combatRepository->findLogById($id);
        if ($log) {
            $log->attacker_units = json_decode($log->attacker_units, true);
            $log->defender_units = json_decode($log->defender_units, true);
        }
        return $log;
    }
}