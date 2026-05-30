<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\UnitRepositoryInterface;
use sdo\Repositories\Interfaces\ManpowerRepositoryInterface;
use sdo\Repositories\Interfaces\CombatRepositoryInterface;
use sdo\Infrastructure\TransactionManager;
use sdo\Services\LogService;
use sdo\Services\ConfigService;
use Exception;
use DateTime;

class BattlefieldService
{
    private const DEFAULT_BATTLEFIELD_LIST_LIMIT = 200;

    public function __construct(
        private TacticalService $tacticalService,
        private LogService $logService,
        private ConfigService $configService,
        private GameService $gameService,
        private DominionRepositoryInterface $dominionRepository,
        private UnitRepositoryInterface $unitRepository,
        private ManpowerRepositoryInterface $manpowerRepository,
        private CombatRepositoryInterface $combatRepository,
        private TransactionManager $transactionManager
    ) {}

    public function getBattlefieldList(): array
    {
        $configuredLimit = (int)$this->configService->get('battlefield_list_limit', self::DEFAULT_BATTLEFIELD_LIST_LIMIT);
        $limit = max(1, min(1000, $configuredLimit));

        return $this->dominionRepository->getBattlefieldList()
            ->take($limit)
            ->map(fn($d) => [
                'kingdom_id' => $d->id,
                'name' => $d->name,
                'username' => $d->user->username ?? 'Unknown',
                'gold' => $d->credits,
                'level' => $this->gameService->calculateLevel((int)$d->xp)
            ])->toArray();
    }

    public function executeAttack(int $attackerId, int $targetId, int $turns): array
    {
        return $this->transactionManager->transaction(function() use ($attackerId, $targetId, $turns) {
            $atkDom = $this->dominionRepository->lockForUpdate($attackerId);
            $defDom = $this->dominionRepository->lockForUpdate($targetId);

            if (!$atkDom || !$defDom) throw new Exception("Targeting uplink lost.");
            if ($atkDom->turns < $turns) throw new Exception("Insufficient strike capacity.");
            if ($attackerId === $targetId) throw new Exception("Self-harm protocol prohibited.");

            // 0. Pull Balance Settings
            $turnsSoftExp = (float)$this->configService->get('battle_atk_turns_soft_exp', 0.50);
            $turnsMaxMult = (float)$this->configService->get('battle_atk_turns_max_mult', 1.35);
            $underdogMinRatio = (float)$this->configService->get('battle_underdog_min_ratio', 0.985);
            $noiseMin = (float)$this->configService->get('battle_random_noise_min', 0.98);
            $noiseMax = (float)$this->configService->get('battle_random_noise_max', 1.02);
            $guardFloor = (int)$this->configService->get('battle_guard_floor', 20000);
            $fullLootCap = (int)$this->configService->get('battle_hourly_full_loot_cap', 5);
            $reducedLootMax = (int)$this->configService->get('battle_hourly_reduced_loot_max', 10);

            // 1. Anti-Farm & Fatigue Checks
            $hourlyCount = $this->combatRepository->countRecentBattlesBetween($attackerId, $targetId, 1);

            $lootFactor = 1.0;
            if ($hourlyCount >= $fullLootCap) $lootFactor = 0.25;
            if ($hourlyCount >= $reducedLootMax) $lootFactor = 0.0;

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

            // Turns Multiplier: 1 + soft_exp * (pow(turns, 0.5) - 1)
            $turnsMult = min(1 + $turnsSoftExp * (pow($turns, 0.5) - 1), $turnsMaxMult);
            $noiseA = mt_rand((int)($noiseMin * 1000), (int)($noiseMax * 1000)) / 1000.0;
            $noiseD = mt_rand((int)($noiseMin * 1000), (int)($noiseMax * 1000)) / 1000.0;

            $ea = $atkRatings['offense'] * $turnsMult * $noiseA;
            $ed = $defRatings['defense'] * $noiseD;
            
            $ratio = $ea / max(1.0, $ed);
            $attackerWins = ($ratio >= $underdogMinRatio);

            // 3. Casualties
            $guardsLost = 0;
            if ($defGuards > $guardFloor) {
                $killFrac = (0.08 + 0.07 * max(0.0, min(1.0, $ratio - 1.0))) * (1 + 0.2 * ($turnsMult - 1.0));
                if (!$attackerWins) $killFrac *= 0.5;
                $guardsLost = min((int)floor($defGuards * $killFrac), $defGuards - $guardFloor);
            }

            // 4. Plunder
            $stolen = 0;
            if ($attackerWins) {
                $stealPct = (0.08 + 0.1 * max(0.0, min(1.0, $ratio - 1.0)));
                $stolen = (int)floor($defDom->credits * min($stealPct, 0.2) * $lootFactor);
            }

            // 5. XP
            $lvlDiff = $this->gameService->calculateLevel((int)$defDom->xp) - $this->gameService->calculateLevel((int)$atkDom->xp);
            $xpGained = (int)(($attackerWins ? mt_rand(150, 200) : mt_rand(40, 60)) * $turns * max(0.1, 1 + ($lvlDiff * 0.07)));

            // 6. Persistence
            $this->dominionRepository->update($attackerId, [
                'credits' => $atkDom->credits + $stolen,
                'turns' => $atkDom->turns - $turns,
                'xp' => $atkDom->xp + $xpGained
            ]);

            $this->dominionRepository->update($targetId, [
                'credits' => $defDom->credits - $stolen
            ]);

            $this->deductUnits($attackerId, 'soldiers', $fatigueLoss);
            $this->deductUnits($targetId, 'guards', $guardsLost);

            $logId = $this->combatRepository->logBattle([
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
        
        $unit = $this->unitRepository->findBySlug($slug);
        if (!$unit) return;

        $this->manpowerRepository->updateQuantity($domId, (int)$unit->id, -$qty);
    }

    public function getBattleLog(int $id): ?object
    {
        return $this->combatRepository->findLogById($id);
    }
}
