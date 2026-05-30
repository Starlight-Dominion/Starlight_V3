<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\UnitRepositoryInterface;
use sdo\Repositories\Interfaces\ManpowerRepositoryInterface;
use sdo\Infrastructure\TransactionManager;
use Exception;

class MinesService
{
    private array $minesConfig;

    public function __construct(
        private UntrainingService $untrainingService,
        private GameService $gameService,
        private DominionRepositoryInterface $dominionRepository,
        private UnitRepositoryInterface $unitRepository,
        private ManpowerRepositoryInterface $manpowerRepository,
        private TransactionManager $transactionManager
    ) {
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
        
        $manpower = $this->manpowerRepository->getManpowerBySlugMap((int)$dominion->id);
        $minersCount = (int)($manpower['workers'] ?? 0);
        
        return (float)($productionPerMiner * $minersCount);
    }

    public function assignMiners(int $dominionId, int $quantity): array
    {
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Quantity must be positive.'];
        }

        try {
            return $this->transactionManager->transaction(function() use ($dominionId, $quantity) {
                $dominion = $this->dominionRepository->lockForUpdate($dominionId);
                if (!$dominion) throw new Exception('Sector not found.');

                $workerUnit = $this->unitRepository->findBySlug('workers');
                if (!$workerUnit) throw new Exception('Worker unit configuration not found.');

                $totalCreditsCost = 200 * $quantity;
                $totalCitizenCost = 1 * $quantity;

                if ($dominion->credits < $totalCreditsCost) {
                    throw new Exception('Insufficient credits.');
                }
                if ($dominion->citizens < $totalCitizenCost) {
                    throw new Exception('Insufficient citizens.');
                }

                $this->dominionRepository->update($dominionId, [
                    'credits' => $dominion->credits - $totalCreditsCost,
                    'citizens' => $dominion->citizens - $totalCitizenCost
                ]);

                $this->manpowerRepository->updateQuantity($dominionId, (int)$workerUnit->id, $quantity);

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
            return $this->transactionManager->transaction(function() use ($dominionId) {
                $dominion = $this->dominionRepository->lockForUpdate($dominionId);
                if (!$dominion) throw new Exception('Sector not found.');

                $currentTier = (int)($dominion->current_mine_tier ?? 1);
                $currentLevel = (int)($dominion->current_mine_level ?? 1);
                $nextTier = $currentTier + 1;

                if ($nextTier > 10) {
                    throw new Exception("Maximum extraction depth reached for current tech.");
                }

                $playerLevel = $this->gameService->calculateLevel((int)$dominion->xp);
                $requiredLevel = $this->minesConfig['unlocks'][$nextTier];
                if ($playerLevel < $requiredLevel) {
                    throw new Exception("Commander level {$requiredLevel} required for next extraction tier.");
                }

                $newLevel = (int)floor($currentLevel / 2);

                $this->dominionRepository->update($dominionId, [
                    'current_mine_tier' => $nextTier,
                    'current_mine_level' => $newLevel
                ]);

                return ['success' => true, 'message' => "Upgraded to Extraction Tier {$nextTier}! Previous efforts translated to Level {$newLevel}."];
            });
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function upgradeCurrentMine(int $dominionId): array
    {
        try {
            return $this->transactionManager->transaction(function() use ($dominionId) {
                $dominion = $this->dominionRepository->lockForUpdate($dominionId);
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

                $this->dominionRepository->update($dominionId, [
                    'credits' => $dominion->credits - $cost,
                    'current_mine_level' => $nextLevel
                ]);

                return ['success' => true, 'message' => "Extraction Tier {$currentTier} upgraded to Level {$nextLevel}."];
            });
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
