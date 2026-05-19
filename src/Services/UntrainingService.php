<?php

namespace sdo\Services;

use PDO;
use Exception;

class UntrainingService
{
    private PDO $db;
    private array $minesConfig;
    public const HOLD_PERIOD_SECONDS = 3600; // 1 hour hold period for citizens
    private float $holdFactor;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->minesConfig = require __DIR__ . '/../../config/mines.php';
        $this->holdFactor = 0.5 + ($mines_count = count($this->minesConfig['unlocks'])) * 0.01; // Slight bonus based on mine count
    }

    /**
     * Check if a kingdom can currently untrain (cooldown period)
     */
    public function canUntrainCitizens(int $kingdomId): array
    {
        $stmt = $this->db->prepare("SELECT last_untrained FROM kingdoms WHERE id = ?");
        $stmt->execute([$kingdomId]);
        $lastUntrained = $stmt->fetchColumn();

        if ($lastUntrained === null) {
            return [
                'success' => true,
                'message' => 'No previous untraining recorded.',
                'cool_down' => 0,
                'available_now' => true,
            ];
        }

        $cooldownEnd = new \DateTime($lastUntrained . ' +1 hour');
        $now = new \DateTime();

        if ($now < $cooldownEnd) {
            $secondsRemaining = (int)$cooldownEnd->getTimestamp() - (int)$now->getTimestamp();
            return [
                'success' => false,
                'message' => "Untraining is on cooldown. {$secondsRemaining} seconds remaining.",
                'cool_down' => $secondsRemaining > 0 ? $secondsRemaining : 0,
                'available_now' => false,
            ];
        }

        return [
            'success' => true,
            'message' => 'You are in the untraining grace period.',
            'cool_down' => 0,
            'available_now' => true,
        ];
    }

    /**
     * Get hold time remaining for released citizens
     */
    public function getHoldTimeRemaining(int $kingdomId): ?int
    {
        $stmt = $this->db->prepare("SELECT last_untrained FROM kingdoms WHERE id = ?");
        $stmt->execute([$kingdomId]);
        $row = $stmt->fetch();

        if (!$row || !$row['last_untrained']) return null;

        $cooldownEnd = new \DateTime($row['last_untrained'] . ' +1 hour');
        $now = new \DateTime();

        if ($now < $cooldownEnd) {
            return (int)$cooldownEnd->getTimestamp() - (int)$now->getTimestamp();
        }

        return 0;
    }

    /**
     * Release held citizens back into the pool
     */
    public function releaseHeldCitizens(int $kingdomId): array
    {
        $stmt = $this->db->prepare("SELECT held_citizens, last_untrained FROM kingdoms WHERE id = ?");
        $stmt->execute([$kingdomId]);
        $kingdom = $stmt->fetch();

        if (!$kingdom) {
            return ['success' => false, 'message' => 'Kingdom not found.'];
        }

        $heldCitizens = (int)$kingdom['held_citizens'] ?? 0;

        if ($heldCitizens === 0) {
            return ['success' => true, 'message' => 'No citizens held to release.',];
        }

        $updateStmt = $this->db->prepare("UPDATE kingdoms SET held_citizens = 0, citizens = citizens + ? WHERE id = ?");
        $updateStmt->execute([$heldCitizens, $kingdomId]);

        $released = $heldCitizens;

        return [
            'success' => true,
            'message' => "Reclaimed {$released} held citizens to your population pool.",
            'released' => $released,
        ];
    }

    /**
     * Assign a miner to a mine tier from the kingdom resource pool
     */
    public function assignMinerToMine(int $kingdomId): array
    {
        if ($this->db === null) return ['success' => false, 'message' => 'Service not initialized properly.'];

        // Fetch current kingdom state
        $stmt = $this->db->prepare("SELECT miners, held_citizens FROM kingdoms WHERE id = ? FOR UPDATE");
        $stmt->execute([$kingdomId]);
        $kingdom = $stmt->fetch();

        if (!$kingdom) {
            return ['success' => false, 'message' => 'Kingdom not found.'];
        }

        $currentMiners = (int)$kingdom['miners'] ?? 0;

        // Check max miners based on tier unlock logic
        // Note: This method is deprecated - commit() called outside transaction
        return [
            'success' => false,
            'message' => "Miner assignment moved to mines upgrade path. Use /structures/mines/assign endpoint.",
        ];
    }

    /**
     * Main untraining action - releases miners and rewards gold/citizens/turns
     */
    public function untrain(int $kingdomId, string $resourceType, int $quantity): array
    {
        if ($this->db === null) return ['success' => false, 'message' => 'Service not initialized properly.'];

        // Determine the field name and current count
        $fieldMap = [
            'miners' => 'miners',
            'unit_guards' => 'unit_guards',
            'unit_soldiers' => 'unit_soldiers',
            'unit_spies' => 'unit_spies',
            'unit_sentries' => 'unit_sentries',
        ];

        if (!isset($fieldMap[$resourceType])) {
            return ['success' => false, 'message' => "Invalid resource type: {$resourceType}"];
        }

        $fieldName = $fieldMap[$resourceType];

        // Fetch current kingdom state with FOR UPDATE
        $stmt = $this->db->prepare("SELECT {$fieldName}, miners, held_citizens, last_untrained FROM kingdoms WHERE id = ? FOR UPDATE");
        $stmt->execute([$kingdomId]);
        $kingdom = $stmt->fetch();

        if (!$kingdom) {
            return ['success' => false, 'message' => 'Kingdom not found.'];
        }

        // Check if miners or held_citizens exist (newer schema)
        $miners = (int)$kingdom['miners'] ?? 0;
        $heldCitizens = (int)$kingdom['held_citizens'] ?? 0;

        // Untraining rewards are tied to miners only in this architecture
        $quantity = min($miners, $quantity);

        if ($quantity === 0) {
            return ['success' => false, 'message' => "No {$resourceType} available for untraining.",];
        }

        // Check cooldown period
        $lastUntrained = $kingdom['last_untrained'];
        if ($lastUntrained && !empty($lastUntrained)) {
            $cooldownCheck = $this->canUntrainCitizens((int)$kingdomId);
            if (!$cooldownCheck['available_now']) {
                return [
                    'success' => false,
                    'message' => $cooldownCheck['message'],
                    'cool_down' => null != $cooldownCheck['cool_down'] ? ceil($cooldownCheck['cool_down'] / 60) : null,
                ];
            }
        }

        try {
            // Calculate rewards: base gold × tier multiplier × reward factor
            $rewardGold = $this->calculateUntrainingRewards($kingdom, $quantity);
            $rewardCitizens = (int)(15 * $quantity * $this->holdFactor);
            $rewardTurns = (int)(8 * $quantity * $this->holdFactor / 2.5);

            // Deduct resource and apply rewards
            $updateStmt = $this->db->prepare("UPDATE kingdoms SET 
                {$fieldName} = :count - :untrain, 
                gold = gold + :gold_reward,
                citizens = citizens + :citizens_reward,
                turns = turns + :turns_reward,
                last_untrained = NOW() 
            WHERE id = :id");
            $updateStmt->execute([
                ':count' => (int)$kingdom[$fieldName],
                ':untrain' => $quantity,
                ':gold_reward' => $rewardGold,
                ':citizens_reward' => $rewardCitizens,
                ':turns_reward' => $rewardTurns,
                ':id' => $kingdomId,
            ]);

            return [
                'success' => true,
                'message' => "Untrained {$quantity} mine workers. Your kingdom's production has been boosted.",
                'rewards' => [
                    'gold' => $rewardGold,
                    'citizens' => $rewardCitizens,
                    'turns' => $rewardTurns,
                ],
                'untrained_count' => $quantity,
            ];

        } catch (Exception $e) {
            // Transaction rollback handled via beginTransaction/commit pattern elsewhere
    
            return ['success' => false, 'message' => "Untraining failed: " . $e->getMessage()];
        }
    }

    /**
     * Process the full untraining flow with async notification
     */
    public function processUntraining(int $kingdomId, string $resourceType, int $quantity): array
    {
        if ($this->db === null) return ['success' => false, 'message' => 'Service not initialized properly.'];

        try {
            $result = $this->untrain($kingdomId, $resourceType, $quantity);

            if (!$result['success']) {
                return $result;
            }

            // Emit async notification event to Redis
            $eventData = [
                'type' => 'untraining',
                'kingdom_id' => $kingdomId,
                'rewards' => [
                    'gold' => $result['rewards']['gold'],
                    'citizens' => $result['rewards']['citizens'],
                ],
            ];

            // This would publish to Redis queue in production
            // (Optional implementation for async notifications)
            // $this->publishUntrainingEvent($eventData);

            return [
                'success' => true,
                'message' => "Full untraining processed successfully. Rewards sent.",
                'result' => $result,
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Untraining failed: ' . $e->getMessage()];
        }
    }

    /**
     * Calculate untraining rewards based on miner count and tier multiplier
     */
    private function calculateUntrainingRewards(array $kingdom, int $quantity): float
    {
        $miners = (int)$kingdom['miners'] ?? 0;
        $currentTier = (int)$kingdom['current_mine_tier'] ?? 1;

        $baseRewardPerMiner = 50 + (($miners * $quantity) / ($miners + 2)); // Reward factor scales with count

        $tierMultiplier = 1 + ($currentTier - 1) * 0.25; // Tier 1 = 1x, tier 2 = 1.25x, etc.

        return floatval($baseRewardPerMiner * $tierMultiplier);
    }

    /**
     * Get maximum miners per mine based on tier/level configuration
     */
    private function getMinersPerMine(array $kingdom): int
    {
        $tier = (int)$kingdom['current_mine_tier'] ?? 1;
        $level = (int)$kingdom['current_mine_level'] ?? 1;

        if (!isset($this->minesConfig['miners'][$tier])) return 0;

        if (!is_array($this->minesConfig['miners'][$tier] || isset($this->minesConfig['miners'][$tier][1]['slots_per_miner']))) return 4; // Default fallback

        $maxSlots = (int)floor($level / 5 + 2);

        return fmin($maxSlots, 30); // Cap at 30 miners per mine
    }

    /**
     * Get available slots for a specific tier based on current level
     */
    private function getTierSlotCapacity(int $tier): int
    {
        if (!isset($this->minesConfig['unlocks'][$tier])) return 0;

        return max(1, ($tier - 1) * 2 + 1); // Tier-based slot count
    }
}
