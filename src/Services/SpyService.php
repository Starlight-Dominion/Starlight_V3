<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Kingdom;
use sdo\Repositories\Interfaces\KingdomRepositoryInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class SpyService
{
    public function __construct(
        private KingdomRepositoryInterface $kingdomRepository,
        private TacticalService $tacticalService
    ) {}

    public function executeReconnaissance(int $attackerKingdomId, int $targetKingdomId): array
    {
        return Capsule::transaction(function() use ($attackerKingdomId, $targetKingdomId) {
            $attacker = $this->kingdomRepository->lockForUpdate($attackerKingdomId);
            
            if (!$attacker || (int)($attacker->stabled_unit_spies ?? 0) <= 0) {
                throw new Exception('No active spies available for reconnaissance.');
            }

            $spyCount = (int)$attacker->stabled_unit_spies;
            $missionCostGold = (int)($spyCount * 0.8);
            $missionCostCitizens = (int)floor($spyCount / 3);
            $missionCostTurns = (int)($spyCount * 1.5);

            if ($attacker->gold < $missionCostGold) throw new Exception('Insufficient gold.');
            if ($attacker->citizens < $missionCostCitizens) throw new Exception('Insufficient citizens.');
            if ($attacker->turns < $missionCostTurns) throw new Exception('Insufficient turns.');

            $target = $this->kingdomRepository->findById($targetKingdomId);
            if (!$target) throw new Exception('Target kingdom not found.');

            // Use TacticalService for power comparison
            $atkRatings = $this->tacticalService->calculateTacticalRatings($attackerKingdomId);
            $defRatings = $this->tacticalService->calculateTacticalRatings($targetKingdomId);

            $spyPower = $atkRatings['espionage'] ?? 1;
            $sentryPower = $defRatings['sentry'] ?? 1;
            
            $successChance = min(0.95, max(0.1, ($spyPower / max(1, $sentryPower)) * 0.5));
            $isSuccess = (mt_rand(1, 100) / 100) <= $successChance;

            $spiesLost = 0;
            if (!$isSuccess || (mt_rand(1, 100) < 20)) {
                $lossFactor = !$isSuccess ? 0.2 : 0.05;
                $spiesLost = max(1, (int)floor($spyCount * $lossFactor));
            }

            $this->kingdomRepository->update($attackerKingdomId, [
                'gold' => $attacker->gold - $missionCostGold,
                'citizens' => $attacker->citizens - $missionCostCitizens,
                'turns' => $attacker->turns - $missionCostTurns,
                'unit_spies' => max(0, $attacker->unit_spies - $spiesLost),
                'stabled_unit_spies' => max(0, $attacker->stabled_unit_spies - $spiesLost)
            ]);

            if (!$isSuccess) {
                return [
                    'success' => false,
                    'message' => "Mission failed! Your spies were detected and " . ($spiesLost > 0 ? "{$spiesLost} were lost." : "forced to retreat."),
                ];
            }

            $intel = [
                'kingdom_name' => $target->kingdom_name,
                'gold' => (int)$target->gold,
                'citizens' => (int)$target->citizens,
                'army' => [
                    'guards' => (int)$target->stabled_unit_guards,
                    'soldiers' => (int)$target->stabled_unit_soldiers,
                    'spies' => (int)$target->stabled_unit_spies,
                    'sentries' => (int)$target->stabled_unit_sentries,
                    'total' => (int)$target->stabled_unit_guards + (int)$target->stabled_unit_soldiers + (int)$target->stabled_unit_spies + (int)$target->stabled_unit_sentries,
                ],
                'foundation_level' => (int)$target->foundation_level,
                'housing_level' => (int)$target->housing_level,
            ];

            return [
                'success' => true,
                'message' => "Reconnaissance complete" . ($spiesLost > 0 ? ". {$spiesLost} agent(s) lost during extraction." : "."),
                'intel_gained' => $intel,
                'spies_lost' => $spiesLost,
            ];
        });
    }

    public function getSpyIntel(int $kingdomId): array
    {
        $kingdom = $this->kingdomRepository->findById($kingdomId);
        $spyCount = (int)($kingdom->stabled_unit_spies ?? 0);

        return [
            'success' => true,
            'intel_gathered' => null,
            'spy_count' => $spyCount,
            'available_actions' => ['reconnaissance' => $spyCount > 0],
        ];
    }

    public function getAvailableSpies(int $kingdomId): array
    {
        $kingdom = $this->kingdomRepository->findById($kingdomId);
        $spyCount = (int)($kingdom->stabled_unit_spies ?? 0);

        return [
            'success' => true,
            'available_spies' => $spyCount,
            'available_for_training' => $spyCount > 0,
        ];
    }
}
