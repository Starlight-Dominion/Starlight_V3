<?php

declare(strict_types=1);

namespace sdo\Services;

use PDO;
use Exception;
use sdo\Models\Kingdom;
use Illuminate\Database\Capsule\Manager as Capsule;

class StablingService
{
    private array $unitsConfig;
    private const MAINTENANCE_COST_FACTOR = 0.05; // 5% of unit cost per upkeep tick for active units

    public function __construct()
    {
        $this->unitsConfig = require __DIR__ . '/../../config/units.php';
    }

    private function getPdo(): \PDO
    {
        return Capsule::connection()->getPdo();
    }

    private function getStructure(): array
    {
        $stmt = $this->getPdo()->prepare("SELECT * FROM structures WHERE slug = 'stable'");
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private function getStableConfig(): array
    {
        $structure = $this->getStructure();
        $stmt = $this->getPdo()->prepare("SELECT level, cost, capacity FROM structure_levels WHERE structure_id = ? ORDER BY level ASC");
        $stmt->execute([$structure['id']]);
        $levels = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $config = [];
        foreach ($levels as $level) {
            $config[$level['level']] = $level;
        }
        return $config;
    }

    /**
     * Get detailed stabling data for a kingdom
     */
    public function getStableData(int $kingdomId): array
    {
        $kingdom = Kingdom::findOrFail($kingdomId);
        $stableConfig = $this->getStableConfig();
        $structure = $this->getStructure();
        
        $currentLevel = (int)$kingdom->stable_level;
        $maxStableLevel = (int)$kingdom->foundation_level * (int)$structure['dependency_multiplier'];
        
        $currentCapacity = $stableConfig[$currentLevel]['capacity'] ?? 0;
        
        $nextLevel = $currentLevel + 1;
        $upgradeCost = $stableConfig[$nextLevel]['cost'] ?? null;
        $nextCapacity = $stableConfig[$nextLevel]['capacity'] ?? null;

        $stabledCounts = [
            'guards' => (int)$kingdom->stabled_unit_guards,
            'soldiers' => (int)$kingdom->stabled_unit_soldiers,
            'spies' => (int)$kingdom->stabled_unit_spies,
            'sentries' => (int)$kingdom->stabled_unit_sentries,
        ];

        $totalStabled = array_sum($stabledCounts);
        $availableCapacity = max(0, $currentCapacity - $totalStabled);

        $idleCounts = [
            'guards' => max(0, (int)$kingdom->unit_guards - (int)$kingdom->stabled_unit_guards),
            'soldiers' => max(0, (int)$kingdom->unit_soldiers - (int)$kingdom->stabled_unit_soldiers),
            'spies' => max(0, (int)$kingdom->unit_spies - (int)$kingdom->stabled_unit_spies),
            'sentries' => max(0, (int)$kingdom->unit_sentries - (int)$kingdom->stabled_unit_sentries),
        ];

        $maintenanceCost = $this->calculateMaintenance($stabledCounts);

        return [
            'kingdom' => $kingdom,
            'stable_level' => $currentLevel,
            'max_stable_level' => $maxStableLevel,
            'current_capacity' => $currentCapacity,
            'total_stabled' => $totalStabled,
            'available_capacity' => $availableCapacity,
            'upgrade_cost' => $upgradeCost,
            'next_capacity' => $nextCapacity,
            'stabled_unit_counts' => $stabledCounts,
            'idle_unit_counts' => $idleCounts,
            'maintenance_cost' => $maintenanceCost,
        ];
    }

    /**
     * Move units from idle to stabled status
     */
    public function stableUnits(int $kingdomId, string $unitType, int $quantity): array
    {
        if (!isset($this->unitsConfig[$unitType]) || $quantity <= 0) {
            throw new Exception("Invalid unit type or quantity.");
        }

        return Capsule::transaction(function() use ($kingdomId, $unitType, $quantity) {
            $kingdom = Kingdom::lockForUpdate()->find($kingdomId);
            
            $totalStabled = (int)$kingdom->stabled_unit_guards + 
                            (int)$kingdom->stabled_unit_soldiers + 
                            (int)$kingdom->stabled_unit_spies + 
                            (int)$kingdom->stabled_unit_sentries;
            
            $stableConfig = $this->getStableConfig();
            $capacity = $stableConfig[(int)$kingdom->stable_level]['capacity'] ?? 0;
            
            if ($totalStabled + $quantity > $capacity) {
                throw new Exception("Stable capacity exceeded. Upgrade your stable for more space.");
            }

            $idleField = 'unit_' . $unitType;
            $stabledField = 'stabled_unit_' . $unitType;

            $idleCount = (int)$kingdom->$idleField - (int)$kingdom->$stabledField;

            if ($quantity > $idleCount) {
                throw new Exception("Not enough idle {$unitType} units to stable.");
            }

            $kingdom->$stabledField += $quantity;
            $kingdom->save();

            return ['success' => true, 'message' => "Successfully stabled {$quantity} {$unitType} units."];
        });
    }

    /**
     * Upgrade the stable structure
     */
    public function upgradeStable(int $kingdomId): array
    {
        return Capsule::transaction(function() use ($kingdomId) {
            $kingdom = Kingdom::lockForUpdate()->find($kingdomId);
            $structure = $this->getStructure();
            
            $currentLevel = (int)$kingdom->stable_level;
            $nextLevel = $currentLevel + 1;
            
            if ($nextLevel > $structure['max_level']) {
                throw new Exception("Stable is already at maximum level.");
            }

            $maxAllowedLevel = (int)$kingdom->foundation_level * (int)$structure['dependency_multiplier'];
            if ($nextLevel > $maxAllowedLevel) {
                throw new Exception("Foundation Level " . ceil($nextLevel / (int)$structure['dependency_multiplier']) . " required to upgrade Stable to Level {$nextLevel}.");
            }

            $stableConfig = $this->getStableConfig();
            $cost = $stableConfig[$nextLevel]['cost'] ?? null;
            if ($cost === null) {
                throw new Exception("Upgrade configuration not found for level {$nextLevel}.");
            }

            if ($kingdom->gold < $cost) {
                throw new Exception("Insufficient gold for upgrade. Required: " . number_format($cost) . " GP.");
            }

            $kingdom->gold -= $cost;
            $kingdom->stable_level = $nextLevel;
            $kingdom->save();

            return ['success' => true, 'message' => "Stable upgraded to level {$nextLevel}."];
        });
    }

    /**
     * Calculate tick maintenance for stabled units
     */
    public function calculateMaintenance(array $stabledCounts): int
    {
        $totalMaintenance = 0;
        foreach ($stabledCounts as $type => $count) {
            $costGold = (int)($this->unitsConfig[$type]['cost_gold'] ?? 0);
            $totalMaintenance += ($costGold * self::MAINTENANCE_COST_FACTOR) * $count;
        }
        return (int)$totalMaintenance;
    }

    /**
     * Provided for compatibility but now only returns static info as stabling is separate from training
     */
    public function getStableUnitDetails(): array
    {
        $details = [];
        foreach ($this->unitsConfig as $type => $config) {
            $details[$type] = [
                'name' => $config['name'],
                'description' => $config['description'],
                'stats' => [
                    'offense' => (int)($config['power_offense'] ?? 0),
                    'defense' => (int)($config['power_defense'] ?? 0),
                ],
                'cost' => [
                    'gold' => (int)$config['cost_gold'],
                    'citizens' => (int)$config['cost_citizens'],
                    'turns' => (int)$config['cost_turns'],
                ]
            ];
        }
        return $details;
    }
}
