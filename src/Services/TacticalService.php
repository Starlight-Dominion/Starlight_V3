<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use Illuminate\Database\Capsule\Manager as Capsule;

class TacticalService
{
    private array $unitsConfig;

    public function __construct()
    {
        $this->unitsConfig = require __DIR__ . '/../../config/units.php';
    }

    /**
     * Calculates tactical ratings using the new Dominion architecture.
     */
    public function calculateTacticalRatings(int $dominionId): array
    {
        $dominion = Dominion::findOrFail($dominionId);
        
        $ratings = [
            'offense' => 0.0,
            'defense' => 0.0,
            'espionage' => 0.0,
            'sentry' => 0.0,
            'foundation_hp' => (int)$dominion->foundation_hp
        ];

        // 1. Calculate Stabled Unit Power
        foreach ($this->unitsConfig as $slug => $config) {
            // We check 'stabled_unit_' columns added in previous migrations
            $count = (int)($dominion->{"stabled_unit_{$slug}"} ?? 0);
            if ($count <= 0) continue;

            $unitOffense = (float)$config['power_offense'];
            $unitDefense = (float)$config['power_defense'];

            $ratings['offense'] += $count * $unitOffense;
            $ratings['defense'] += $count * $unitDefense;
            
            if ($slug === 'spies') $ratings['espionage'] += $count * $unitOffense;
            if ($slug === 'sentries') $ratings['sentry'] += $count * $unitDefense;
        }

        // 2. Battle Weighting Formula
        $ratings['weighted_power'] = pow($ratings['offense'] * 1.1, 0.3) * pow($ratings['defense'] * 0.9, 0.3);

        return $ratings;
    }

    public function getTacticalOverview(int $dominionId): array
    {
        $ratings = $this->calculateTacticalRatings($dominionId);
        $dominion = Dominion::find($dominionId);

        return [
            'ratings' => $ratings,
            'foundation' => [
                'hp' => $dominion->foundation_hp,
                'max_hp' => $dominion->foundation_max_hp
            ],
            'army' => [
                'guards' => (int)($dominion->stabled_unit_guards ?? 0),
                'soldiers' => (int)($dominion->stabled_unit_soldiers ?? 0),
                'spies' => (int)($dominion->stabled_unit_spies ?? 0),
                'sentries' => (int)($dominion->stabled_unit_sentries ?? 0),
            ]
        ];
    }
}