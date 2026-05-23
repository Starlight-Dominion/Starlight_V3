<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Models\DominionManpower;
use sdo\Models\DominionStructure;
use sdo\Models\DominionArmoryItem;
use sdo\Models\StructureLevel;
use sdo\Models\ArmoryItem;
use sdo\Models\Unit;
use Illuminate\Database\Capsule\Manager as Capsule;

class TacticalService
{
    private const AVG_UNIT_POWER = 10;

    public function calculateTacticalRatings(int $dominionId): array
    {
        $dom = Dominion::findOrFail($dominionId);
        
        $manpower = DominionManpower::with('unit')
            ->where('dominion_id', $dominionId)
            ->get()
            ->mapWithKeys(fn($m) => [$m->unit->slug => $m->total_quantity]);

        $soldiers = (int)($manpower['soldiers'] ?? 0);
        $guards = (int)($manpower['guards'] ?? 0);

        // Attribute Multipliers (1% per point)
        $strengthMult = 1 + ($dom->strength_points * 0.01);
        $constitutionMult = 1 + ($dom->constitution_points * 0.01);

        // 1:1 Equipping Logic
        $atkArmoryBonus = $this->getArmoryBonus($dominionId, 'soldiers', $soldiers, 'attack_bonus');
        $defArmoryBonus = $this->getArmoryBonus($dominionId, 'guards', $guards, 'defense_bonus');

        // Structural Multipliers (From dominion_structures table)
        $structs = DominionStructure::join('structure_levels', function($j) {
                $j->on('dominion_structures.structure_id', '=', 'structure_levels.structure_id')
                  ->on('dominion_structures.level', '=', 'structure_levels.level');
            })
            ->where('dominion_structures.dominion_id', $dominionId)
            ->selectRaw('SUM(buff_offense) as off, SUM(buff_defense) as def')
            ->first();

        $offenseUpgradeMult = 1 + (($structs->off ?? 0) / 100.0);
        $defenseUpgradeMult = 1 + (($structs->def ?? 0) / 100.0);

        // Legacy Formula
        $rawAttack = (($soldiers * self::AVG_UNIT_POWER * $strengthMult) + $atkArmoryBonus) * $offenseUpgradeMult;
        $rawDefense = (($guards * self::AVG_UNIT_POWER * $constitutionMult) + $defArmoryBonus) * $defenseUpgradeMult;

        return [
            'offense' => (int)$rawAttack,
            'defense' => (int)$rawDefense,
            'army' => $manpower->toArray()
        ];
    }

    private function getArmoryBonus(int $domId, string $type, int $unitCount, string $field): float
    {
        if ($unitCount <= 0) return 0.0;

        $items = DominionArmoryItem::with('item')
            ->where('kingdom_id', $domId)
            ->whereHas('item', fn($q) => $q->where('unit_type', $type))
            ->get();

        $bonus = 0.0;
        foreach ($items as $item) {
            // Only as many items as we have units can provide a bonus
            $effectiveQty = min($unitCount, $item->quantity);
            $bonus += ($effectiveQty * (float)$item->item->$field);
        }
        return $bonus;
    }

    public function getTacticalOverview(int $dominionId): array
    {
        $res = $this->calculateTacticalRatings($dominionId);
        $dom = Dominion::find($dominionId);

        $manpowerDetails = DominionManpower::with('unit')
            ->where('dominion_id', $dominionId)
            ->get()
            ->map(fn($m) => [
                'slug' => $m->unit->slug,
                'name' => $m->unit->name,
                'quantity' => (int)$m->total_quantity
            ])->toArray();

        return [
            'ratings' => ['offense' => $res['offense'], 'defense' => $res['defense']],
            'army' => $res['army'],
            'manpower' => $manpowerDetails,
            'foundation' => ['hp' => $dom->foundation_hp, 'max_hp' => $dom->foundation_max_hp]
        ];
    }
}
