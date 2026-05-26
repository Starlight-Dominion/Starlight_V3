<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Models\DominionManpower;
use sdo\Models\Unit;
use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;

class MinesService
{
    private UntrainingService $untrainingService;
    private array $minesConfig;

    public function __construct(UntrainingService $untrainingService)
    {
        $this->untrainingService = $untrainingService;
        $this->minesConfig = require __DIR__ . '/../../config/mines.php';
    }

    public function getMinesConfig(): array
    {
        return $this->minesConfig;
    }

    public function calculateCurrentProduction(Dominion $dominion): float
    {
        $tier = (int)($dominion->current_mine_tier ?? 1);
        $level = (int)($dominion->current_mine_level ?? 1);
        $productionPerMiner = $this->minesConfig['mines'][$tier][$level]['production_per_miner'] ?? 0;
        
        $workerUnit = Unit::where('slug', 'workers')->first();
        $minersCount = 0;
        if ($workerUnit) {
            $minersCount = DominionManpower::where('dominion_id', $dominion->id)
                ->where('unit_id', $workerUnit->id)
                ->value('total_quantity') ?? 0;
        }
        
        return (float)($productionPerMiner * $minersCount);
    }

    public function assignMiners(int $dominionId, int $quantity): array
    {
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Quantity must be positive.'];
        }

        try {
            return Capsule::transaction(function() use ($dominionId, $quantity) {
                $dominion = Dominion::lockForUpdate()->find($dominionId);
                if (!$dominion) throw new Exception('Sector not found.');

                $workerUnit = Unit::where('slug', 'workers')->first();
                if (!$workerUnit) throw new Exception('Worker unit configuration not found.');

                $totalCreditsCost = 200 * $quantity;
                $totalCitizenCost = 1 * $quantity;

                if ($dominion->credits < $totalCreditsCost) {
                    throw new Exception('Insufficient credits.');
                }
                if ($dominion->citizens < $totalCitizenCost) {
                    throw new Exception('Insufficient citizens.');
                }

                $dominion->decrement('credits', $totalCreditsCost);
                $dominion->decrement('citizens', $totalCitizenCost);
                $dominion->save();

                $exists = DominionManpower::where('dominion_id', $dominionId)
                    ->where('unit_id', $workerUnit->id)
                    ->exists();

                if ($exists) {
                    DominionManpower::where('dominion_id', $dominionId)
                        ->where('unit_id', $workerUnit->id)
                        ->increment('total_quantity', $quantity);
                } else {
                    DominionManpower::create([
                        'dominion_id' => $dominionId,
                        'unit_id' => $workerUnit->id,
                        'total_quantity' => $quantity
                    ]);
                }

                return ['success' => true, 'message' => "Assigned {$quantity} utility workers to extraction duty."];
            });
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function unassignMiners(int $dominionId, int $quantity): array
    {
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Quantity must be positive.'];
        }
        return $this->untrainingService->untrain($dominionId, 'workers', $quantity);
    }

    public function upgradeMineTier(int $dominionId): array
    {
        try {
            return Capsule::transaction(function() use ($dominionId) {
                $dominion = Dominion::lockForUpdate()->find($dominionId);
                if (!$dominion) throw new Exception('Sector not found.');

                $currentTier = (int)($dominion->current_mine_tier ?? 1);
                $currentLevel = (int)($dominion->current_mine_level ?? 1);
                $nextTier = $currentTier + 1;

                if ($nextTier > 10) {
                    throw new Exception("Maximum extraction depth reached for current tech.");
                }

                $playerLevel = $dominion->getPlayerLevel();
                $requiredLevel = $this->minesConfig['unlocks'][$nextTier];
                if ($playerLevel < $requiredLevel) {
                    throw new Exception("Commander level {$requiredLevel} required for next extraction tier.");
                }

                $newLevel = (int)floor($currentLevel / 2);

                $dominion->current_mine_tier = $nextTier;
                $dominion->current_mine_level = $newLevel;
                $dominion->save();

                return ['success' => true, 'message' => "Upgraded to Extraction Tier {$nextTier}! Previous efforts translated to Level {$newLevel}."];
            });
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function upgradeCurrentMine(int $dominionId): array
    {
        try {
            return Capsule::transaction(function() use ($dominionId) {
                $dominion = Dominion::lockForUpdate()->find($dominionId);
                if (!$dominion) throw new Exception('Sector not found.');

                $currentTier = (int)($dominion->current_mine_tier ?? 1);
                $currentLevel = (int)($dominion->current_mine_level ?? 1);
                $nextLevel = $currentLevel + 1;

                if ($nextLevel > 150) {
                    throw new Exception("Maximum efficiency reached for this extraction site.");
                }

                $cost = $this->minesConfig['mines'][$currentTier][$nextLevel]['cost'];

                if ($dominion->credits < $cost) {
                    throw new Exception("Insufficient credits for infrastructure upgrade.");
                }

                $dominion->decrement('credits', $cost);
                $dominion->current_mine_level = $nextLevel;
                $dominion->save();

                return ['success' => true, 'message' => "Extraction Tier {$currentTier} upgraded to Level {$nextLevel}."];
            });
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
