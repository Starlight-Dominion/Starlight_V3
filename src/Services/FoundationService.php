<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Kingdom;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class FoundationService
{
    private function getPdo(): \PDO
    {
        return Capsule::connection()->getPdo();
    }

    private function getStructure(): array
    {
        $stmt = $this->getPdo()->prepare("SELECT * FROM structures WHERE slug = 'foundation'");
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private function getTiers(int $structureId): array
    {
        $stmt = $this->getPdo()->prepare("SELECT * FROM structure_levels WHERE structure_id = ? ORDER BY level ASC");
        $stmt->execute([$structureId]);
        $tiers = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $config = [];
        foreach ($tiers as $tier) {
            $config[$tier['level']] = $tier;
            // Map consistent keys
            $config[$tier['level']]['name'] = $tier['buff_name'];
            $config[$tier['level']]['hp'] = $tier['buff_hp'];
        }
        return $config;
    }

    private function getUpgrades(int $structureId): array
    {
        $stmt = $this->getPdo()->prepare("SELECT * FROM structure_upgrade_options WHERE structure_id = ?");
        $stmt->execute([$structureId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $upgrades = [];
        foreach ($rows as $row) {
            $upgrades[$row['slug']] = $row;
        }
        return $upgrades;
    }

    public function getFoundationData(int $kingdomId): array
    {
        $kingdom = Kingdom::findOrFail($kingdomId);
        $currentLevel = (int)$kingdom->foundation_level;
        $playerLevel = $kingdom->getPlayerLevel();

        $structure = $this->getStructure();
        $tiers = $this->getTiers((int)$structure['id']);
        $upgrades = $this->getUpgrades((int)$structure['id']);

        $currentTier = $tiers[$currentLevel] ?? null;
        if ($currentTier) {
            $currentTier['id'] = $currentLevel;
        }

        $nextLevel = $currentLevel + 1;
        $nextTier = $tiers[$nextLevel] ?? null;

        return [
            'kingdom' => $kingdom,
            'player_level' => $playerLevel,
            'current_tier' => $currentTier,
            'next_tier' => $nextTier,
            'all_tiers' => $tiers,
            'upgrades' => $upgrades
        ];
    }

    public function upgradeFoundation(int $kingdomId): array
    {
        return Capsule::transaction(function() use ($kingdomId) {
            $kingdom = Kingdom::lockForUpdate()->find($kingdomId);
            $currentLevel = (int)$kingdom->foundation_level;
            $nextLevel = $currentLevel + 1;

            $structure = $this->getStructure();
            $tiers = $this->getTiers((int)$structure['id']);
            $nextTier = $tiers[$nextLevel] ?? null;

            if (!$nextTier) throw new Exception("Maximum foundation tier reached.");

            if ($kingdom->getPlayerLevel() < $nextTier['player_level_req']) {
                throw new Exception("Player level {$nextTier['player_level_req']} required for this tier.");
            }

            if ($kingdom->gold < $nextTier['cost']) {
                throw new Exception("Insufficient gold for upgrade.");
            }

            $kingdom->gold -= $nextTier['cost'];
            $kingdom->foundation_level = $nextLevel;
            
            // Calculate base HP and apply upgrades
            $baseHp = $nextTier['buff_hp'];
            if ($kingdom->foundation_upgrade_slot_1) {
                $upgrades = $this->getUpgrades((int)$structure['id']);
                $installed = $upgrades[$kingdom->foundation_upgrade_slot_1] ?? null;
                if ($installed && $installed['bonus_type'] === 'hp_percentage') {
                    $baseHp *= (1 + $installed['bonus_value']);
                }
            }
            
            $kingdom->foundation_hp = (int)floor($baseHp);
            $kingdom->save();

            return ['success' => true, 'message' => "Foundation upgraded to Level {$nextLevel}: {$nextTier['buff_name']}."];
        });
    }

    public function purchaseUpgrade(int $kingdomId, string $upgradeKey): array
    {
        return Capsule::transaction(function() use ($kingdomId, $upgradeKey) {
            $structure = $this->getStructure();
            $upgrades = $this->getUpgrades((int)$structure['id']);
            
            if (!isset($upgrades[$upgradeKey])) throw new Exception("Invalid upgrade.");

            $kingdom = Kingdom::lockForUpdate()->find($kingdomId);
            if (!empty($kingdom->foundation_upgrade_slot_1)) throw new Exception("Upgrade slot is already filled.");

            $upgrade = $upgrades[$upgradeKey];
            $tiers = $this->getTiers((int)$structure['id']);
            $currentTier = $tiers[$kingdom->foundation_level] ?? null;
            
            if (!$currentTier) throw new Exception("No foundation tier found.");
            
            $upgradeCost = (int)($currentTier['cost'] * $upgrade['cost_multiplier']);

            if ($kingdom->gold < $upgradeCost) throw new Exception("Insufficient gold.");

            $kingdom->gold -= $upgradeCost;
            $kingdom->foundation_upgrade_slot_1 = $upgradeKey;

            // Recalculate HP immediately
            $baseHp = $currentTier['buff_hp'];
            if ($upgrade['bonus_type'] === 'hp_percentage') {
                $baseHp *= (1 + $upgrade['bonus_value']);
            }
            
            $kingdom->foundation_hp = (int)floor($baseHp);
            $kingdom->save();

            return ['success' => true, 'message' => "Installed {$upgrade['name']}."];
        });
    }
}
