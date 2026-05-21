<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use Illuminate\Database\Capsule\Manager as Capsule;

class TacticalService
{
    public function calculateTacticalRatings(int $dominionId): array
    {
        $dominion = Dominion::findOrFail($dominionId);
        
        $ratings = [
            'offense' => 0.0,
            'defense' => 0.0,
            'espionage' => 0.0,
            'sentry' => 0.0,
            'total_units' => 0,
            'foundation_hp' => (int)$dominion->foundation_hp
        ];

        // Join manpower directly with unit definitions
        $manpower = Capsule::table('dominion_manpower')
            ->join('units', 'dominion_manpower.unit_id', '=', 'units.id')
            ->where('dominion_manpower.dominion_id', $dominionId)
            ->get();

        foreach ($manpower as $m) {
            $qty = (int)$m->total_quantity;
            if ($qty <= 0) continue;

            $ratings['offense'] += $qty * (float)$m->power_offense;
            $ratings['defense'] += $qty * (float)$m->power_defense;
            $ratings['total_units'] += $qty;

            if ($m->slug === 'spies') $ratings['espionage'] += $qty * (float)$m->power_offense;
            if ($m->slug === 'sentries') $ratings['sentry'] += $qty * (float)$m->power_defense;
        }

        // Apply Battle Weighting
        $ratings['weighted_power'] = pow($ratings['offense'] * 1.1, 0.3) * pow($ratings['defense'] * 0.9, 0.3);

        return $ratings;
    }

    public function getTacticalOverview(int $dominionId): array
    {
        $ratings = $this->calculateTacticalRatings($dominionId);
        $dominion = Dominion::find($dominionId);

        // Fetch detailed roster for UI
        $roster = Capsule::table('dominion_manpower')
            ->join('units', 'dominion_manpower.unit_id', '=', 'units.id')
            ->where('dominion_manpower.dominion_id', $dominionId)
            ->select('units.slug', 'dominion_manpower.total_quantity')
            ->get()
            ->pluck('total_quantity', 'slug')
            ->toArray();

        return [
            'ratings' => $ratings,
            'foundation' => [
                'hp' => $dominion->foundation_hp,
                'max_hp' => $dominion->foundation_max_hp
            ],
            'army' => $roster
        ];
    }
}