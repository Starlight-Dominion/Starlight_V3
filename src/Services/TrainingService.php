<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Kingdom;
use sdo\Repositories\Interfaces\KingdomRepositoryInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class TrainingService
{
    public function __construct(
        private KingdomRepositoryInterface $kingdomRepository
    ) {}

    public function getUnitConfig(): array
    {
        $units = Capsule::table('units')->orderBy('cost_gold', 'asc')->get();
        
        $config = [];
        foreach ($units as $unit) {
            $config[$unit->slug] = (array)$unit;
        }
        return $config;
    }

    public function train(int $kingdomId, string $unitType, int $quantity): array
    {
        $units = $this->getUnitConfig();
        if (!isset($units[$unitType]) || $quantity <= 0) {
            return ['success' => false, 'message' => 'Invalid unit type or quantity.'];
        }

        try {
            return Capsule::transaction(function() use ($kingdomId, $unitType, $quantity, $units) {
                $kingdom = $this->kingdomRepository->lockForUpdate($kingdomId);
                if (!$kingdom) {
                    throw new Exception("Kingdom not found.");
                }
                
                $unit = $units[$unitType];

                // 1. Structure Requirements
                if (($kingdom->foundation_level ?? 0) < ($unit['foundation_level_req'] ?? 0)) {
                    throw new Exception("Foundation level {$unit['foundation_level_req']} required.");
                }
                if (($kingdom->stable_level ?? 0) < ($unit['stable_level_req'] ?? 0)) {
                    throw new Exception("Stable level {$unit['stable_level_req']} required.");
                }
                if (($kingdom->armory_level ?? 0) < ($unit['armory_level_req'] ?? 0)) {
                    throw new Exception("Armory level {$unit['armory_level_req']} required.");
                }

                // 2. Unit Requirements (Prerequisite)
                if (!empty($unit['requirement_slug'])) {
                    $prereqField = 'unit_' . $unit['requirement_slug'];
                    if (($kingdom->$prereqField ?? 0) <= 0) {
                        throw new Exception("You must own at least one {$unit['requirement_slug']} first.");
                    }
                }

                // 3. Resource Costs
                $goldCost = $unit['cost_gold'] * $quantity;
                $citizenCost = $unit['cost_citizens'] * $quantity;
                $turnsCost = $unit['cost_turns'] * $quantity;

                if ($kingdom->gold < $goldCost) throw new Exception('Insufficient gold.');
                if ($kingdom->citizens < $citizenCost) throw new Exception('Insufficient citizens.');
                if ($kingdom->turns < $turnsCost) throw new Exception('Insufficient turns.');

                $field = 'unit_' . $unitType;
                
                $this->kingdomRepository->update($kingdomId, [
                    'gold' => $kingdom->gold - $goldCost,
                    'citizens' => $kingdom->citizens - $citizenCost,
                    'turns' => $kingdom->turns - $turnsCost,
                    $field => ($kingdom->$field ?? 0) + $quantity
                ]);

                return ['success' => true, 'message' => "Trained {$quantity} {$unit['name']}."];
            });
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}