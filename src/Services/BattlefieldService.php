<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Models\User;
use sdo\Services\LogService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;
use DateTime;

class BattlefieldService
{
    // Balance Constants from Legacy
    private const ATK_TURNS_SOFT_EXP = 0.50;
    private const ATK_TURNS_MAX_MULT = 1.35;
    private const UNDERDOG_MIN_RATIO = 0.985;
    private const RANDOM_NOISE_MIN = 0.98;
    private const RANDOM_NOISE_MAX = 1.02;
    private const GUARD_FLOOR = 20000;
    private const HOURLY_FULL_LOOT_CAP = 5;
    private const HOURLY_REDUCED_LOOT_MAX = 10;

    public function __construct(
        private TacticalService $tacticalService,
        private LogService $logService
    ) {}

    public function getBattlefieldList(): array
    {
        return Dominion::with('user')
            ->orderBy('credits', 'desc')
            ->get()
            ->map(fn($d) => [
                'kingdom_id' => $d->id,
                'name' => $d->name,
                'username' => $d->user->username,
                'gold' => $d->credits,
                'level' => $d->getPlayerLevel()
            ])->toArray();
    }

    public function executeAttack(int $attackerId, int $targetId, int $turns): array
    {
        return Capsule::transaction(function() use ($attackerId, $targetId, $turns) {
            $atkDom = Dominion::lockForUpdate()->find($attackerId);
            $defDom = Dominion::lockForUpdate()->find($targetId);

            if (!$atkDom || !$defDom) throw new Exception("Targeting uplink lost.");
            if ($atkDom->turns < $turns) throw new Exception("Insufficient strike capacity.");
            if ($attackerId === $targetId) throw new Exception("Self-harm protocol prohibited.");

            // 1. Anti-Farm & Fatigue Checks
            $hourAgo = (new DateTime('-1 hour'))->format('Y-m-d H:i:s');
            $hourlyCount = Capsule::table('battle_logs')
                ->where('attacker_id', $attackerId)
                ->where('defender_id', $targetId)
                ->where('battle_time', '>', $hourAgo)
                ->count();

            $lootFactor = 1.0;
            if ($hourlyCount >= self::HOURLY_FULL_LOOT_CAP) $lootFactor = 0.25;
            if ($hourlyCount >= self::HOURLY_REDUCED_LOOT_MAX) $lootFactor = 0.0;

            // 2. Power Calculation
            $atkRatings = $this->tacticalService->calculateTacticalRatings($attackerId);
            $defRatings = $this->tacticalService->calculateTacticalRatings($targetId);

            $atkSoldiers = (int)($atkRatings['army']['soldiers'] ?? 0);
            $defGuards = (int)($defRatings['army']['guards'] ?? 0);

            if ($atkSoldiers <= 0) throw new Exception("No expeditionary force available.");

            $fatigueLoss = 0;
            if ($hourlyCount >= 10) {
                $fatigueLoss = (int)floor($atkSoldiers * 0.01 * ($hourlyCount - 9));
            }

            // Turns Multiplier: 1 + 0.5 * (pow(turns, 0.5) - 1)
            $turnsMult = min(1 + 0.5 * (pow($turns, 0.5) - 1), self::ATK_TURNS_MAX_MULT);
            $noiseA = mt_rand((int)(self::RANDOM_NOISE_MIN * 1000), (int)(self::RANDOM_NOISE_MAX * 1000)) / 1000.0;
            $noiseD = mt_rand((int)(self::RANDOM_NOISE_MIN * 1000), (int)(self::RANDOM_NOISE_MAX * 1000)) / 1000.0;

            $ea = $atkRatings['offense'] * $turnsMult * $noiseA;
            $ed = $defRatings['defense'] * $noiseD;
            
            $ratio = $ea / max(1.0, $ed);
            $attackerWins = ($ratio >= self::UNDERDOG_MIN_RATIO);

            // 3. Casualties
            $guardsLost = 0;
            if ($defGuards > self::GUARD_FLOOR) {
                $killFrac = (0.08 + 0.07 * max(0.0, min(1.0, $ratio - 1.0))) * (1 + 0.2 * ($turnsMult - 1.0));
                if (!$attackerWins) $killFrac *= 0.5;
                $guardsLost = min((int)floor($defGuards * $killFrac), $defGuards - self::GUARD_FLOOR);
            }

            // 4. Plunder
            $stolen = 0;
            if ($attackerWins) {
                $stealPct = (0.08 + 0.1 * max(0.0, min(1.0, $ratio - 1.0)));
                $stolen = (int)floor($defDom->credits * min($stealPct, 0.2) * $lootFactor);
            }

            // 5. XP
            $lvlDiff = $defDom->getPlayerLevel() - $atkDom->getPlayerLevel();
            $xpGained = (int)(($attackerWins ? mt_rand(150, 200) : mt_rand(40, 60)) * $turns * max(0.1, 1 + ($lvlDiff * 0.07)));

            // 6. Persistence
            $atkDom->credits += $stolen;
            $atkDom->turns -= $turns;
            $atkDom->xp += $xpGained;
            $atkDom->save();

            $defDom->credits -= $stolen;
            $defDom->save();

            $this->deductUnits($attackerId, 'soldiers', $fatigueLoss);
            $this->deductUnits($targetId, 'guards', $guardsLost);

            $logId = Capsule::table('battle_logs')->insertGetId([
                'attacker_id' => $attackerId,
                'defender_id' => $targetId,
                'attacker_name' => $atkDom->name,
                'defender_name' => $defDom->name,
                'outcome' => $attackerWins ? 'victory' : 'defeat',
                'credits_stolen' => $stolen,
                'turns_used' => $turns,
                'attacker_damage' => (int)$ea,
                'defender_damage' => (int)$ed,
                'attacker_xp_gained' => $xpGained,
                'guards_lost' => $guardsLost,
                'attacker_soldiers_lost' => $fatigueLoss,
                'loot_factor' => $lootFactor,
                'battle_time' => (new DateTime())->format('Y-m-d H:i:s')
            ]);

            // Comprehensive Logging
            $this->logService->log(
                $attackerId,
                'battle_attack',
                "Commander launched an assault against {$defDom->name}. Outcome: " . strtoupper($attackerWins ? 'victory' : 'defeat'),
                $stolen,
                ['defender_id' => $targetId, 'battle_log_id' => $logId, 'turns_used' => $turns]
            );

            $this->logService->log(
                $targetId,
                'battle_defend',
                "Commander's sector was attacked by {$atkDom->name}. Outcome: " . strtoupper($attackerWins ? 'defeat' : 'victory'),
                $stolen,
                ['attacker_id' => $attackerId, 'battle_log_id' => $logId]
            );

            return [
                'success' => true,
                'battle_id' => $logId,
                'message' => $attackerWins ? "Dominion Victorious." : "Assault Repelled."
            ];
        });
    }

    private function deductUnits(int $domId, string $slug, int $qty): void
    {
        if ($qty <= 0) return;
        
        $unit = Capsule::table('units')->where('slug', $slug)->first();
        if (!$unit) return;

        Capsule::table('dominion_manpower')
            ->where('dominion_id', $domId)
            ->where('unit_id', $unit->id)
            ->decrement('total_quantity', $qty);
    }

    public function getBattleLog(int $id): ?object
    {
        return Capsule::table('battle_logs')->find($id);
    }
}
