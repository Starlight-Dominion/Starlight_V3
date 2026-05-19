<?php

namespace sdo\Services;

use PDO;
use Exception;

class MinesService
{
    private PDO $db;
    private UntrainingService $untrainingService;
    private array $minesConfig;

    public function __construct(PDO $db, UntrainingService $untrainingService)
    {
        $this->db = $db;
        $this->untrainingService = $untrainingService;
        $this->minesConfig = require __DIR__ . '/../../config/mines.php';
    }

    public function getMinesConfig(): array
    {
        return $this->minesConfig;
    }

    public function calculateCurrentProduction(array $kingdom): float
    {
        $tier = $kingdom['current_mine_tier'];
        $level = $kingdom['current_mine_level'];
        $productionPerMiner = $this->minesConfig['mines'][$tier][$level]['production_per_miner'] ?? 0;
        
        return $productionPerMiner * $kingdom['miners'];
    }

    public function assignMiners(int $kingdomId, int $quantity): array
    {
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Quantity must be positive.'];
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT gold, citizens, turns FROM kingdoms WHERE id = ? FOR UPDATE");
            $stmt->execute([$kingdomId]);
            $kingdom = $stmt->fetch();

            $totalGoldCost = 200 * $quantity;
            $totalCitizenCost = 1 * $quantity;
            $totalTurnsCost = 1 * $quantity;

            if ($kingdom['gold'] < $totalGoldCost) {
                throw new Exception('Insufficient gold.');
            }
            if ($kingdom['citizens'] < $totalCitizenCost) {
                throw new Exception('Insufficient citizens.');
            }
            if ($kingdom['turns'] < $totalTurnsCost) {
                throw new Exception('Insufficient turns.');
            }

            $updateStmt = $this->db->prepare(
                "UPDATE kingdoms SET 
                    gold = gold - :gold,
                    citizens = citizens - :citizens,
                    turns = turns - :turns,
                    miners = miners + :quantity
                 WHERE id = :id"
            );
            $updateStmt->execute([
                ':gold' => $totalGoldCost,
                ':citizens' => $totalCitizenCost,
                ':turns' => $totalTurnsCost,
                ':quantity' => $quantity,
                ':id' => $kingdomId,
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => "Assigned {$quantity} citizens to mining."];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function unassignMiners(int $kingdomId, int $quantity): array
    {
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Quantity must be positive.'];
        }
        return $this->untrainingService->untrain($kingdomId, 'miners', $quantity);
    }

    public function upgradeMineTier(int $kingdomId): array
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM kingdoms WHERE id = ? FOR UPDATE");
            $stmt->execute([$kingdomId]);
            $kingdom = $stmt->fetch();

            $currentTier = $kingdom['current_mine_tier'];
            $currentLevel = $kingdom['current_mine_level'];
            $nextTier = $currentTier + 1;

            if ($nextTier > 10) {
                throw new Exception("You are already at the highest mine tier.");
            }

            $playerLevel = floor(sqrt($kingdom['xp'] / 100)) + 1; // Example formula
            $requiredLevel = $this->minesConfig['unlocks'][$nextTier];
            if ($playerLevel < $requiredLevel) {
                throw new Exception("You must be at least player level {$requiredLevel} to unlock the next mine tier.");
            }

            $newLevel = floor($currentLevel / 2);

            $updateStmt = $this->db->prepare(
                "UPDATE kingdoms SET 
                    current_mine_tier = :new_tier,
                    current_mine_level = :new_level
                 WHERE id = :id"
            );
            $updateStmt->execute([
                ':new_tier' => $nextTier,
                ':new_level' => $newLevel,
                ':id' => $kingdomId,
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => "Upgraded to Mine Tier {$nextTier}! Your previous mine's efforts have granted you Level {$newLevel}."];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function upgradeCurrentMine(int $kingdomId): array
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM kingdoms WHERE id = ? FOR UPDATE");
            $stmt->execute([$kingdomId]);
            $kingdom = $stmt->fetch();

            $currentTier = $kingdom['current_mine_tier'];
            $currentLevel = $kingdom['current_mine_level'];
            $nextLevel = $currentLevel + 1;

            if ($nextLevel > 150) {
                throw new Exception("This mine is already at max level.");
            }

            $cost = $this->minesConfig['mines'][$currentTier][$nextLevel]['cost'];

            if ($kingdom['gold'] < $cost) {
                throw new Exception("Insufficient gold to upgrade.");
            }

            $updateStmt = $this->db->prepare(
                "UPDATE kingdoms SET 
                    gold = gold - :cost,
                    current_mine_level = :new_level
                 WHERE id = :id"
            );
            $updateStmt->execute([
                ':cost' => $cost,
                ':new_level' => $nextLevel,
                ':id' => $kingdomId,
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => "Mine Tier {$currentTier} upgraded to level {$nextLevel}."];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
