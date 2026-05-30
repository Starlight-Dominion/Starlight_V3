<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\UnitRepositoryInterface;
use sdo\Repositories\Interfaces\ManpowerRepositoryInterface;
use sdo\Repositories\Interfaces\StructureRepositoryInterface;
use sdo\Infrastructure\TransactionManager;
use sdo\Services\LogService;
use Exception;

class UpgradesService
{
    private array $housingConfig;
    private array $mercenaryMarketConfig;

    public function __construct(
        private DominionRepositoryInterface $dominionRepository,
        private UnitRepositoryInterface $unitRepository,
        private ManpowerRepositoryInterface $manpowerRepository,
        private StructureRepositoryInterface $structureRepository,
        private TransactionManager $transactionManager,
        private LogService $logService
    ) {
        $this->housingConfig = require __DIR__ . '/../../config/housing.php';
        $this->mercenaryMarketConfig = require __DIR__ . '/../../config/mercenary_market.php';
    }

    public function getUpgradeData(int $dominionId): array
    {
        $dominion = $this->dominionRepository->findById($dominionId);
        if (!$dominion) throw new Exception("Dominion not found.");

        return [
            'dominion' => $dominion,
            'housing_config' => $this->housingConfig,
            'mercenary_market_config' => $this->mercenaryMarketConfig,
        ];
    }

    public function upgradeHousing(int $dominionId): array
    {
        try {
            return $this->transactionManager->transaction(function() use ($dominionId) {
                $dominion = $this->dominionRepository->lockForUpdate($dominionId);
                if (!$dominion) throw new Exception("Dominion not found.");
                
                $currentLevel = (int)($dominion->housing_level ?? 1);
                $nextLevel = $currentLevel + 1;

                if ($nextLevel > $this->housingConfig['max_level']) {
                    throw new Exception("Housing infrastructure is already at peak efficiency.");
                }

                $cost = $this->housingConfig['levels'][$nextLevel]['cost'];

                if ($dominion->credits < $cost) {
                    throw new Exception("Insufficient credits for housing expansion.");
                }

                $this->dominionRepository->update($dominionId, [
                    'credits' => $dominion->credits - $cost,
                    'housing_level' => $nextLevel
                ]);

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
            return $this->transactionManager->transaction(function() use ($dominionId) {
                $dominion = $this->dominionRepository->lockForUpdate($dominionId);
                if (!$dominion) throw new Exception("Dominion not found.");
                
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

                $this->dominionRepository->update($dominionId, [
                    'credits' => $dominion->credits - $cost,
                    'mercenary_market_level' => $nextLevel
                ]);

                // Grant units
                $unitsToGrant = [
                    'guards' => $levelData['guards'] ?? 0,
                    'soldiers' => $levelData['soldiers'] ?? 0,
                    'spies' => $levelData['spies'] ?? 0,
                    'sentries' => $levelData['sentries'] ?? 0
                ];

                foreach ($unitsToGrant as $slug => $qty) {
                    if ($qty > 0) {
                        $unit = $this->unitRepository->findBySlug($slug);
                        if ($unit) {
                            $this->manpowerRepository->updateQuantity($dominionId, (int)$unit->id, $qty);
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
