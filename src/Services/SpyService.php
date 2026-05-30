<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\UnitRepositoryInterface;
use sdo\Repositories\Interfaces\ManpowerRepositoryInterface;
use sdo\Infrastructure\TransactionManager;
use Exception;

class SpyService
{
    public function __construct(
        private DominionRepositoryInterface $dominionRepository,
        private UnitRepositoryInterface $unitRepository,
        private ManpowerRepositoryInterface $manpowerRepository,
        private TacticalService $tacticalService,
        private GameService $gameService,
        private TransactionManager $transactionManager
    ) {}

    public function executeReconnaissance(int $attackerDominionId, int $targetDominionId): array
    {
        return $this->transactionManager->transaction(function() use ($attackerDominionId, $targetDominionId) {
            $attacker = $this->dominionRepository->lockForUpdate($attackerDominionId);
            if (!$attacker) throw new Exception('Attacker sector not found.');
            
            $spyUnit = $this->unitRepository->findBySlug('spies');
            if (!$spyUnit) throw new Exception('Spy unit configuration not found.');

            $manpower = $this->manpowerRepository->getManpowerBySlugMap($attackerDominionId);
            $spyCount = (int)($manpower['spies'] ?? 0);

            if ($spyCount <= 0) {
                throw new Exception('No active spies available for reconnaissance.');
            }

            $missionCostCredits = (int)($spyCount * 0.8);
            $missionCostCitizens = (int)floor($spyCount / 3);
            $missionCostTurns = 5;

            if ($attacker->credits < $missionCostCredits) throw new Exception('Insufficient credits.');
            if ($attacker->citizens < $missionCostCitizens) throw new Exception('Insufficient citizens.');
            if ($attacker->turns < $missionCostTurns) throw new Exception('Insufficient strike capacity.');

            $target = $this->dominionRepository->findById($targetDominionId);
            if (!$target) throw new Exception('Target sector not found.');

            // Use TacticalService for power comparison
            $atkRatings = $this->tacticalService->calculateTacticalRatings($attackerDominionId);
            $defRatings = $this->tacticalService->calculateTacticalRatings($targetDominionId);

            $spyPower = $atkRatings['espionage'] ?? 1;
            $sentryPower = $defRatings['sentry'] ?? 1;
            
            $successChance = min(0.95, max(0.1, ($spyPower / max(1, $sentryPower)) * 0.5));
            $isSuccess = (mt_rand(1, 100) / 100) <= $successChance;

            $spiesLost = 0;
            if (!$isSuccess || (mt_rand(1, 100) < 20)) {
                $lossFactor = !$isSuccess ? 0.2 : 0.05;
                $spiesLost = max(1, (int)floor($spyCount * $lossFactor));
            }

            $this->dominionRepository->update($attackerDominionId, [
                'credits' => $attacker->credits - $missionCostCredits,
                'citizens' => $attacker->citizens - $missionCostCitizens,
                'turns' => $attacker->turns - $missionCostTurns
            ]);

            if ($spiesLost > 0) {
                $this->manpowerRepository->updateQuantity($attackerDominionId, (int)$spyUnit->id, -$spiesLost);
            }

            if (!$isSuccess) {
                return [
                    'success' => false,
                    'message' => "Mission failed! Your spies were detected and " . ($spiesLost > 0 ? "{$spiesLost} were lost." : "forced to retreat."),
                ];
            }

            // Gather Intel
            $targetManpower = $this->manpowerRepository->getManpowerBySlugMap($targetDominionId);

            $intel = [
                'name' => $target->name,
                'credits' => (int)$target->credits,
                'citizens' => (int)$target->citizens,
                'army' => [
                    'guards' => (int)($targetManpower['guards'] ?? 0),
                    'soldiers' => (int)($targetManpower['soldiers'] ?? 0),
                    'spies' => (int)($targetManpower['spies'] ?? 0),
                    'sentries' => (int)($targetManpower['sentries'] ?? 0),
                    'total' => $targetManpower->sum(),
                ],
                'foundation_hp' => (int)$target->foundation_hp,
                'level' => $this->gameService->calculateLevel((int)$target->xp),
            ];

            return [
                'success' => true,
                'message' => "Reconnaissance complete" . ($spiesLost > 0 ? ". {$spiesLost} agent(s) lost during extraction." : "."),
                'intel_gained' => $intel,
                'spies_lost' => $spiesLost,
            ];
        });
    }

    public function getSpyIntel(int $dominionId): array
    {
        $manpower = $this->manpowerRepository->getManpowerBySlugMap($dominionId);
        $spyCount = (int)($manpower['spies'] ?? 0);

        return [
            'success' => true,
            'intel_gathered' => null,
            'spy_count' => (int)$spyCount,
            'available_actions' => ['reconnaissance' => $spyCount > 0],
        ];
    }

    public function getAvailableSpies(int $dominionId): array
    {
        $manpower = $this->manpowerRepository->getManpowerBySlugMap($dominionId);
        $spyCount = (int)($manpower['spies'] ?? 0);

        return [
            'success' => true,
            'available_spies' => (int)$spyCount,
            'available_for_training' => $spyCount > 0,
        ];
    }
}
