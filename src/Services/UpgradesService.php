<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Models\Unit;
use sdo\Models\DominionManpower;
use sdo\Services\LogService;
use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;

class UpgradesService
{
    private array $housingConfig;
    private array $mercenaryMarketConfig;

    public function __construct(private LogService $logService)
    {
        $this->housingConfig = require __DIR__ . '/../../config/housing.php';
        $this->mercenaryMarketConfig = require __DIR__ . '/../../config/mercenary_market.php';
    }

    public function getUpgradeData(int $dominionId): array
    {
        $dominion = Dominion::findOrFail($dominionId);

        return [
            'dominion' => $dominion,
            'housing_config' => $this->housingConfig,
            'mercenary_market_config' => $this->mercenaryMarketConfig,
        ];
    }

    public function upgradeHousing(int $dominionId): array
    {
        try {
            return Capsule::transaction(function() use ($dominionId) {
                $dominion = Dominion::lockForUpdate()->find($dominionId);
                
                $currentLevel = (int)($dominion->housing_level ?? 1);
                $nextLevel = $currentLevel + 1;

                if ($nextLevel > $this->housingConfig['max_level']) {
                    throw new Exception("Housing infrastructure is already at peak efficiency.");
                }

                $cost = $this->housingConfig['levels'][$nextLevel]['cost'];

                if ($dominion->credits < $cost) {
                    throw new Exception("Insufficient credits for housing expansion.");
                }

                $dominion->decrement('credits', $cost);
                $dominion->housing_level = $nextLevel;
                $dominion->save();

                $this->logService->log(
                    $dominionId,
                    'upgrade_housing',
                    "Commander expanded residential sectors to Tier {$nextLevel}.",
                    $cost
                );

                return ['success' => true, 'message' => "Housing expanded to Tier {$nextLevel}."];
            });
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function upgradeMercenaryMarket(int $dominionId): array
    {
        try {
            return Capsule::transaction(function() use ($dominionId) {
                $dominion = Dominion::lockForUpdate()->find($dominionId);
                
                $currentLevel = (int)($dominion->mercenary_market_level ?? 0);
                $nextLevel = $currentLevel + 1;

                if ($nextLevel > $this->mercenaryMarketConfig['max_level']) {
                    throw new Exception("Mercenary contact network is already at maximum reach.");
                }

                $levelData = $this->mercenaryMarketConfig['levels'][$nextLevel];
                $cost = $levelData['cost'];

                if ($dominion->credits < $cost) {
                    throw new Exception("Insufficient credits to secure new mercenary contracts.");
                }

                $dominion->decrement('credits', $cost);
                $dominion->mercenary_market_level = $nextLevel;
                $dominion->save();

                // Grant units
                $unitsToGrant = [
                    'guards' => $levelData['guards'] ?? 0,
                    'soldiers' => $levelData['soldiers'] ?? 0,
                    'spies' => $levelData['spies'] ?? 0,
                    'sentries' => $levelData['sentries'] ?? 0
                ];

                foreach ($unitsToGrant as $slug => $qty) {
                    if ($qty > 0) {
                        $unit = Unit::where('slug', $slug)->first();
                        if ($unit) {
                            $exists = DominionManpower::where('dominion_id', $dominionId)
                                ->where('unit_id', $unit->id)
                                ->exists();

                            if ($exists) {
                                DominionManpower::where('dominion_id', $dominionId)
                                    ->where('unit_id', $unit->id)
                                    ->increment('total_quantity', $qty);
                            } else {
                                DominionManpower::create([
                                    'dominion_id' => $dominionId,
                                    'unit_id' => $unit->id,
                                    'total_quantity' => $qty
                                ]);
                            }
                        }
                    }
                }

                $this->logService->log(
                    $dominionId,
                    'upgrade_mercenary_market',
                    "Commander expanded mercenary network to Tier {$nextLevel}. New recruits acquired.",
                    $cost
                );

                return ['success' => true, 'message' => "Mercenary network expanded to Tier {$nextLevel}. Enforcements deployed."];
            });
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
