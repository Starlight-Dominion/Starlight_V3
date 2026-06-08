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
        $strengthBonus = 1 + ($dom->strength_points * 0.01);
        $constitutionBonus = 1 + ($dom->constitution_points * 0.01);
        $dexterityBonus = 1 + ($dom->dexterity_points * 0.01); // Keeping for consistency
        $charismaBonus = 1 + ($dom->charisma_points * 0.01); // Mapping to defense in legacy? No, charisma is for economy/discount.

        // Structural Multipliers (Legacy style: 1 + buff/100)
        $buffs = $this->dominionStructureRepository->sumMultipleStructureLevelBuffs($dominionId, [
            'off' => 'buff_offense',
            'def' => 'buff_defense'
        ]);

        $offenseMult = 1 + (($buffs['off'] ?? 0) / 100.0);
        $defenseMult = 1 + (($buffs['def'] ?? 0) / 100.0);

        /**
         * Legacy Combat Formulas:
         * offensePower = soldiers * 10 * strengthBonus * offenseMult
         * defenseRating = guards * 10 * constitutionBonus * defenseMult
         * spyOffense = spies * 10 * strengthBonus * offenseMult
         * sentryDefense = sentries * 10 * defenseMult
         */
        $soldiers = (int)($manpowerMap['soldiers'] ?? 0);
        $guards = (int)($manpowerMap['guards'] ?? 0);
        $spies = (int)($manpowerMap['spies'] ?? 0);
        $sentries = (int)($manpowerMap['sentries'] ?? 0);

        $offensePower = (int)floor($soldiers * 10 * $strengthBonus * $offenseMult);
        $defenseRating = (int)floor($guards * 10 * $constitutionBonus * $defenseMult);
        $spyOffense = (int)floor($spies * 10 * $dexterityBonus * $offenseMult);
        $sentryDefense = (int)floor($sentries * 10 * $charismaBonus * $defenseMult);

        return [
            'offense' => $offensePower,
            'defense' => $defenseRating,
            'espionage' => $spyOffense,
            'sentry' => $sentryDefense,
            'army' => $manpowerMap->toArray()
        ];
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
