<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Kingdom;
use Illuminate\Database\Capsule\Manager as Capsule;

class TacticalService
{
    private array $unitsConfig;
    private array $foundationConfig;

    public function __construct()
    {
        $this->unitsConfig = require __DIR__ . '/../../config/units.php';
        $this->foundationConfig = require __DIR__ . '/../../config/foundation.php';
    }

    /**
     * Calculates all tactical ratings for a kingdom, including unit power,
     * equipment bonuses, and global buffs.
     */
    public function calculateTacticalRatings(int $kingdomId): array
    {
        $kingdom = Kingdom::findOrFail($kingdomId);
        
        $ratings = [
            'offense' => 0.0,
            'defense' => 0.0,
            'espionage' => 0.0,
            'sentry' => 0.0,
            'foundation_hp' => (int)$kingdom->foundation_hp
        ];

        // 1. Calculate Unit Base Power + Equipment
        $equipment = $this->getKingdomEquipment($kingdomId);
        
        foreach ($this->unitsConfig as $slug => $config) {
            $count = (int)$kingdom->{"stabled_unit_{$slug}"};
            if ($count <= 0) continue;

            $unitOffense = (float)$config['power_offense'];
            $unitDefense = (float)$config['power_defense'];

            // Add equipment bonuses if applicable
            if (isset($equipment[$slug])) {
                $unitOffense += $equipment[$slug]['attack_bonus'];
                $unitDefense += $equipment[$slug]['defense_bonus'];
            }

            $ratings['offense'] += $count * $unitOffense;
            $ratings['defense'] += $count * $unitDefense;
            
            // Espionage and Sentry are also scaled by units
            // Typically Spies give Espionage, Sentries give Sentry
            if ($slug === 'spies') {
                $ratings['espionage'] += $count * $unitOffense;
            }
            if ($slug === 'sentries') {
                $ratings['sentry'] += $count * $unitDefense;
            }
        }

        // 2. Apply Global Buffs from Foundation Upgrades
        $foundationBuffs = $this->getFoundationBuffs($kingdom);
        
        if (isset($foundationBuffs['defense_percentage'])) {
            $ratings['defense'] *= (1 + $foundationBuffs['defense_percentage']);
        }
        
        // Final Weighting (Battlefield Logic compatibility)
        // BattlefieldService uses: pow($off * 1.1, 0.3) * pow($dfn * 0.9, 0.3)
        // We'll keep raw scores but provide weighted as well
        $ratings['weighted_power'] = pow($ratings['offense'] * 1.1, 0.3) * pow($ratings['defense'] * 0.9, 0.3);

        return $ratings;
    }

    /**
     * Fetches effective equipment bonuses for each unit type.
     * Capped by the number of units (logic for future: assume best items applied first)
     */
    private function getKingdomEquipment(int $kingdomId): array
    {
        $items = Capsule::table('kingdom_armory_items')
            ->join('armory_items', 'kingdom_armory_items.item_id', '=', 'armory_items.id')
            ->where('kingdom_id', $kingdomId)
            ->select('armory_items.unit_type', 'armory_items.attack_bonus', 'armory_items.defense_bonus', 'kingdom_armory_items.quantity')
            ->get();

        $bonuses = [];
        foreach ($items as $item) {
            $uType = $item->unit_type;
            if (!isset($bonuses[$uType])) {
                $bonuses[$uType] = ['attack_bonus' => 0, 'defense_bonus' => 0];
            }
            
            // Simplified for now: Sum all bonuses. 
            // In a more complex game, we'd limit this by (unit_count * slots)
            $bonuses[$uType]['attack_bonus'] += $item->attack_bonus;
            $bonuses[$uType]['defense_bonus'] += $item->defense_bonus;
        }

        return $bonuses;
    }

    private function getFoundationBuffs(Kingdom $kingdom): array
    {
        $buffs = [];
        $upgradeSlot = $kingdom->foundation_upgrade_slot_1;
        
        if ($upgradeSlot && isset($this->foundationConfig['upgrades'][$upgradeSlot])) {
            $upgrade = $this->foundationConfig['upgrades'][$upgradeSlot];
            $buffs[$upgrade['bonus_type']] = $upgrade['bonus_value'];
        }

        return $buffs;
    }

    public function getTacticalOverview(int $kingdomId): array
    {
        $ratings = $this->calculateTacticalRatings($kingdomId);
        $kingdom = Kingdom::find($kingdomId);

        return [
            'ratings' => $ratings,
            'foundation' => [
                'level' => $kingdom->foundation_level,
                'hp' => $kingdom->foundation_hp,
                'upgrade' => $kingdom->foundation_upgrade_slot_1
            ],
            'army' => [
                'guards' => $kingdom->stabled_unit_guards,
                'soldiers' => $kingdom->stabled_unit_soldiers,
                'spies' => $kingdom->stabled_unit_spies,
                'sentries' => $kingdom->stabled_unit_sentries,
            ]
        ];
    }
}