<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Models\DominionManpower;
use sdo\Models\Unit;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class SpyService
{
    public function __construct(
        private DominionRepositoryInterface $dominionRepository,
        private TacticalService $tacticalService
    ) {}

    public function executeReconnaissance(int $attackerDominionId, int $targetDominionId): array
    {
        return Capsule::transaction(function() use ($attackerDominionId, $targetDominionId) {
            $attacker = $this->dominionRepository->lockForUpdate($attackerDominionId);
            
            $spyUnit = Unit::where('slug', 'spies')->first();
            if (!$spyUnit) throw new Exception('Spy unit configuration not found.');

            $attackerSpies = DominionManpower::where('dominion_id', $attackerDominionId)
                ->where('unit_id', $spyUnit->id)
                ->first();

            if (!$attackerSpies || $attackerSpies->total_quantity <= 0) {
                throw new Exception('No active spies available for reconnaissance.');
            }

            $spyCount = $attackerSpies->total_quantity;
            $missionCostCredits = (int)($spyCount * 0.8);
            $missionCostCitizens = (int)floor($spyCount / 3);
            $missionCostTurns = (int)($spyCount * 1.5);

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

            $attacker->credits -= $missionCostCredits;
            $attacker->citizens -= $missionCostCitizens;
            $attacker->turns -= $missionCostTurns;
            $attacker->save();

            if ($spiesLost > 0) {
                $attackerSpies->decrement('total_quantity', $spiesLost);
            }

            if (!$isSuccess) {
                return [
                    'success' => false,
                    'message' => "Mission failed! Your spies were detected and " . ($spiesLost > 0 ? "{$spiesLost} were lost." : "forced to retreat."),
                ];
            }

            // Gather Intel using Eloquent
            $targetManpower = DominionManpower::with('unit')
                ->where('dominion_id', $targetDominionId)
                ->get()
                ->pluck('total_quantity', 'unit.slug');

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
                'level' => $target->getPlayerLevel(),
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
        $spyUnit = Unit::where('slug', 'spies')->first();
        $spyCount = 0;
        if ($spyUnit) {
            $spyCount = DominionManpower::where('dominion_id', $dominionId)
                ->where('unit_id', $spyUnit->id)
                ->value('total_quantity') ?? 0;
        }

        return [
            'success' => true,
            'intel_gathered' => null,
            'spy_count' => (int)$spyCount,
            'available_actions' => ['reconnaissance' => $spyCount > 0],
        ];
    }

    public function getAvailableSpies(int $dominionId): array
    {
        $spyUnit = Unit::where('slug', 'spies')->first();
        $spyCount = 0;
        if ($spyUnit) {
            $spyCount = DominionManpower::where('dominion_id', $dominionId)
                ->where('unit_id', $spyUnit->id)
                ->value('total_quantity') ?? 0;
        }

        return [
            'success' => true,
            'available_spies' => (int)$spyCount,
            'available_for_training' => $spyCount > 0,
        ];
    }
}
