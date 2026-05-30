<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\ManpowerRepositoryInterface;
use sdo\Repositories\Interfaces\DominionStructureRepositoryInterface;
use sdo\Repositories\Interfaces\DominionArmoryRepositoryInterface;

class TacticalService
{
    public function __construct(
        private DominionRepositoryInterface $dominionRepository,
        private ManpowerRepositoryInterface $manpowerRepository,
        private DominionStructureRepositoryInterface $dominionStructureRepository,
        private DominionArmoryRepositoryInterface $dominionArmoryRepository
    ) {}

    public function calculateTacticalRatings(int $dominionId): array
    {
        $dom = $this->dominionRepository->findById($dominionId);
        if (!$dom) return [];
        
        $manpower = $this->manpowerRepository->getManpowerByDominion($dominionId);
        $manpowerMap = $manpower->mapWithKeys(fn($m) => [$m->unit->slug => $m->total_quantity]);

        // Attribute Multipliers (1% per point)
        $strengthMult = 1 + ($dom->strength_points * 0.01);
        $constitutionMult = 1 + ($dom->constitution_points * 0.01);
        $dexterityMult = 1 + ($dom->dexterity_points * 0.01);
        $charismaMult = 1 + ($dom->charisma_points * 0.01);

        $rawAttack = 0;
        $rawDefense = 0;
        $rawEspionage = 0;
        $rawSentry = 0;

        foreach ($manpower as $m) {
            $qty = $m->total_quantity;
            if ($qty > 0) {
                $rawAttack += $qty * (int)$m->unit->power_offense;
                $rawDefense += $qty * (int)$m->unit->power_defense;
                $rawEspionage += $qty * (int)$m->unit->power_spy_offense;
                $rawSentry += $qty * (int)$m->unit->power_spy_defense;
            }
        }

        // 1:1 Equipping Logic
        $atkArmoryBonus = $this->getArmoryBonus($dominionId, 'soldiers', (int)($manpowerMap['soldiers'] ?? 0), 'attack_bonus');
        $defArmoryBonus = $this->getArmoryBonus($dominionId, 'guards', (int)($manpowerMap['guards'] ?? 0), 'defense_bonus');

        // Structural Multipliers
        $buffs = $this->dominionStructureRepository->sumMultipleStructureLevelBuffs($dominionId, [
            'off' => 'buff_offense',
            'def' => 'buff_defense'
        ]);

        $offenseUpgradeMult = 1 + (($buffs['off'] ?? 0) / 100.0);
        $defenseUpgradeMult = 1 + (($buffs['def'] ?? 0) / 100.0);

        // Final Aggregate Ratings
        $rawAttack = ($rawAttack * $strengthMult + $atkArmoryBonus) * $offenseUpgradeMult;
        $rawDefense = ($rawDefense * $constitutionMult + $defArmoryBonus) * $defenseUpgradeMult;
        $rawEspionage = $rawEspionage * $dexterityMult;
        $rawSentry = $rawSentry * $charismaMult;

        return [
            'offense' => (int)$rawAttack,
            'defense' => (int)$rawDefense,
            'espionage' => (int)$rawEspionage,
            'sentry' => (int)$rawSentry,
            'army' => $manpowerMap->toArray()
        ];
    }

    private function getArmoryBonus(int $domId, string $type, int $unitCount, string $field): float
    {
        if ($unitCount <= 0) return 0.0;

        $items = $this->dominionArmoryRepository->getEquippedItemsByType($domId, $type)
            ->sortByDesc(fn($m) => $m->item->$field);

        $bonus = 0.0;
        $remainingCapacity = $unitCount;

        foreach ($items as $item) {
            $effectiveQty = min($remainingCapacity, $item->quantity);
            $bonus += ($effectiveQty * (float)$item->item->$field);
            
            $remainingCapacity -= $effectiveQty;
            if ($remainingCapacity <= 0) break;
        }

        return $bonus;
    }

    public function getTacticalOverview(int $dominionId): array
    {
        $res = $this->calculateTacticalRatings($dominionId);
        $dom = $this->dominionRepository->findById($dominionId);
        if (!$dom) return [];

        $manpowerDetails = $this->manpowerRepository->getManpowerByDominion($dominionId)
            ->map(fn($m) => [
                'slug' => $m->unit->slug,
                'name' => $m->unit->name,
                'quantity' => (int)$m->total_quantity
            ])->toArray();

        return [
            'ratings' => [
                'offense' => $res['offense'],
                'defense' => $res['defense'],
                'espionage' => $res['espionage'],
                'sentry' => $res['sentry']
            ],
            'army' => $res['army'],
            'manpower' => $manpowerDetails,
            'foundation' => ['hp' => $dom->foundation_hp, 'max_hp' => $dom->foundation_max_hp]
        ];
    }
}
