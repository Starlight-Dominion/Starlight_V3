<?php

declare(strict_types=1);

namespace sdo\Services;

use PDO;
use Exception;
use sdo\Models\Kingdom;
use Illuminate\Database\Capsule\Manager as Capsule;

class ArmoryService
{
    public function __construct()
    {
    }

    private function getPdo(): PDO
    {
        return Capsule::connection()->getPdo();
    }

    private function getStructure(): array
    {
        $stmt = $this->getPdo()->prepare("SELECT * FROM structures WHERE slug = 'armory'");
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private function getUpgradeCosts(): array
    {
        $structure = $this->getStructure();
        $stmt = $this->getPdo()->prepare("SELECT level, cost FROM structure_levels WHERE structure_id = ? ORDER BY level ASC");
        $stmt->execute([$structure['id']]);
        $costs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $config = [];
        foreach ($costs as $cost) {
            $config[$cost['level']] = (int)$cost['cost'];
        }
        return $config;
    }

    public function getArmoryData(int $kingdomId): array
    {
        $kingdom = Kingdom::findOrFail($kingdomId);
        $db = $this->getPdo();

        // 1. Fetch Dynamic Configuration
        $unitTypes = $db->query("SELECT * FROM armory_unit_types ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $categories = $db->query("SELECT * FROM armory_categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        
        // 2. Fetch Items with owned quantity and hidden status
        $stmt = $db->prepare("
            SELECT ai.*, 
                   IFNULL(kai.quantity, 0) as owned_quantity,
                   (hai.item_id IS NOT NULL) as is_hidden
            FROM armory_items ai
            LEFT JOIN kingdom_armory_items kai ON ai.id = kai.item_id AND kai.kingdom_id = ?
            LEFT JOIN hidden_armory_items hai ON ai.id = hai.item_id AND hai.kingdom_id = ?
        ");
        $stmt->execute([$kingdomId, $kingdomId]);
        $allItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Assemble Loadouts Structure
        $loadouts = [];
        foreach ($unitTypes as $uType) {
            $loadouts[$uType['slug']] = [
                'title' => $uType['title'],
                'unit_count' => (int)($kingdom->{"unit_{$uType['slug']}"} ?? 0),
                'categories' => []
            ];

            $typeCategories = array_filter($categories, fn($c) => (int)$c['unit_type_id'] === (int)$uType['id']);
            foreach ($typeCategories as $cat) {
                $catItems = array_filter($allItems, fn($i) => (int)$i['category_id'] === (int)$cat['id']);
                
                $itemsConfig = [];
                foreach ($catItems as $item) {
                    $item['unlocked'] = $this->isItemUnlocked($kingdom, $item, $allItems);
                    $itemsConfig[$item['slug']] = $item;
                }

                $loadouts[$uType['slug']]['categories'][$cat['slug']] = [
                    'title' => $cat['name'],
                    'slots' => (int)$cat['slots'],
                    'items' => $itemsConfig
                ];
            }
        }

        $currentLevel = (int)$kingdom->armory_level;
        $nextLevel = $currentLevel + 1;
        $upgradeCosts = $this->getUpgradeCosts();
        $upgradeCost = $upgradeCosts[$nextLevel] ?? null;

        return [
            'loadouts' => $loadouts,
            'gold' => (int)$kingdom->gold,
            'armory_level' => $currentLevel,
            'upgrade_cost' => $upgradeCost
        ];
    }

    public function upgradeArmory(int $kingdomId): array
    {
        return Capsule::transaction(function() use ($kingdomId) {
            $kingdom = Kingdom::lockForUpdate()->find($kingdomId);
            $currentLevel = (int)$kingdom->armory_level;
            $nextLevel = $currentLevel + 1;

            $structure = $this->getStructure();
            if ($nextLevel > $structure['max_level']) throw new Exception("Armory is already at maximum level.");

            $upgradeCosts = $this->getUpgradeCosts();
            $cost = $upgradeCosts[$nextLevel] ?? null;
            if ($cost === null) throw new Exception("Maximum level reached.");

            if ($kingdom->gold < $cost) throw new Exception("Insufficient gold for upgrade.");

            if ($kingdom->foundation_level < $nextLevel) {
                throw new Exception("Foundation Level {$nextLevel} required to upgrade Armory to Level {$nextLevel}.");
            }

            $kingdom->gold -= $cost;
            $kingdom->armory_level = $nextLevel;
            $kingdom->save();

            return ['success' => true, 'message' => "Armory upgraded to level {$nextLevel}."];
        });
    }

    public function toggleHideItem(int $kingdomId, int $itemId): array
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT * FROM hidden_armory_items WHERE kingdom_id = ? AND item_id = ?");
        $stmt->execute([$kingdomId, $itemId]);
        $hidden = $stmt->fetch();

        if ($hidden) {
            $db->prepare("DELETE FROM hidden_armory_items WHERE kingdom_id = ? AND item_id = ?")
                     ->execute([$kingdomId, $itemId]);
            $status = false;
        } else {
            $db->prepare("INSERT INTO hidden_armory_items (kingdom_id, item_id) VALUES (?, ?)")
                     ->execute([$kingdomId, $itemId]);
            $status = true;
        }

        return ['success' => true, 'is_hidden' => $status];
    }

    private function isItemUnlocked(Kingdom $kingdom, array $item, array $allItems): bool
    {
        if ($kingdom->armory_level < ($item['armory_level_req'] ?? 0)) return false;

        if (!empty($item['requirement_slug'])) {
            $prereq = array_values(array_filter($allItems, fn($i) => $i['slug'] === $item['requirement_slug']))[0] ?? null;
            if (!$prereq || (int)$prereq['owned_quantity'] <= 0) return false;
        }

        return true;
    }

    public function buyItem(int $kingdomId, int $itemId, int $quantity): array
    {
        if ($quantity <= 0) throw new Exception("Invalid quantity.");

        return Capsule::transaction(function() use ($kingdomId, $itemId, $quantity) {
            $db = $this->getPdo();
            $kingdom = Kingdom::lockForUpdate()->find($kingdomId);

            $stmt = $db->prepare("SELECT * FROM armory_items WHERE id = ?");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) throw new Exception("Item not found.");

            if ($item['requirement_slug']) {
                $check = $db->prepare("
                    SELECT kai.quantity FROM kingdom_armory_items kai
                    JOIN armory_items ai ON kai.item_id = ai.id
                    WHERE kai.kingdom_id = ? AND ai.slug = ?
                ");
                $check->execute([$kingdomId, $item['requirement_slug']]);
                if ((int)$check->fetchColumn() <= 0) {
                    throw new Exception("You must own the prerequisite item first.");
                }
            }

            if ($kingdom->armory_level < $item['armory_level_req']) {
                throw new Exception("Armory level too low.");
            }

            $totalCost = $item['cost'] * $quantity;
            if ($kingdom->gold < $totalCost) throw new Exception("Insufficient gold.");

            $kingdom->gold -= $totalCost;
            $kingdom->save();

            $db->prepare("
                INSERT INTO kingdom_armory_items (kingdom_id, item_id, quantity)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
            ")->execute([$kingdomId, $itemId, $quantity]);

            return ['success' => true, 'message' => "Purchased {$quantity}x {$item['name']}."];
        });
    }

    public function sellItem(int $kingdomId, int $itemId, int $quantity): array
    {
        return Capsule::transaction(function() use ($kingdomId, $itemId, $quantity) {
            $db = $this->getPdo();
            $kingdom = Kingdom::lockForUpdate()->find($kingdomId);

            $stmt = $db->prepare("
                SELECT ai.*, kai.quantity as owned 
                FROM kingdom_armory_items kai
                JOIN armory_items ai ON kai.item_id = ai.id
                WHERE kai.kingdom_id = ? AND kai.item_id = ?
            ");
            $stmt->execute([$kingdomId, $itemId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item || $item['owned'] < $quantity) throw new Exception("Insufficient stock.");

            $refund = (int)($item['cost'] * 0.5 * $quantity);
            $kingdom->gold += $refund;
            $kingdom->save();

            if ($item['owned'] == $quantity) {
                $db->prepare("DELETE FROM kingdom_armory_items WHERE kingdom_id = ? AND item_id = ?")
                         ->execute([$kingdomId, $itemId]);
            } else {
                $db->prepare("UPDATE kingdom_armory_items SET quantity = quantity - ? WHERE kingdom_id = ? AND item_id = ?")
                         ->execute([$quantity, $kingdomId, $itemId]);
            }

            return ['success' => true, 'message' => "Sold for {$refund} gold."];
        });
    }
}
