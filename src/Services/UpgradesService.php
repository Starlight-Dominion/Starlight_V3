<?php

namespace sdo\Services;

use PDO;
use Exception;

class UpgradesService
{
    private PDO $db;
    private array $housingConfig;
    private array $mercenaryMarketConfig;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->housingConfig = require __DIR__ . '/../../config/housing.php';
        $this->mercenaryMarketConfig = require __DIR__ . '/../../config/mercenary_market.php';
    }

    public function getUpgradeData(int $kingdomId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM kingdoms WHERE id = ?");
        $stmt->execute([$kingdomId]);
        $kingdom = $stmt->fetch();

        return [
            'kingdom' => $kingdom,
            'housing_config' => $this->housingConfig,
            'mercenary_market_config' => $this->mercenaryMarketConfig,
        ];
    }

    public function upgradeHousing(int $kingdomId): array
    {
        $this->db->beginTransaction();
        try {
            $data = $this->getUpgradeData($kingdomId);
            $kingdom = $data['kingdom'];
            $housingConfig = $data['housing_config'];

            $currentLevel = $kingdom['housing_level'];
            $nextLevel = $currentLevel + 1;

            if ($nextLevel > $housingConfig['max_level']) {
                throw new Exception("Housing is already at max level.");
            }

            $cost = $housingConfig['levels'][$nextLevel]['cost'];

            if ($kingdom['gold'] < $cost) {
                throw new Exception("Insufficient gold to upgrade housing.");
            }

            $updateStmt = $this->db->prepare(
                "UPDATE kingdoms SET 
                    gold = gold - :cost,
                    housing_level = :new_level
                 WHERE id = :id"
            );
            $updateStmt->execute([
                ':cost' => $cost,
                ':new_level' => $nextLevel,
                ':id' => $kingdomId,
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => "Housing upgraded to level {$nextLevel}."];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function upgradeMercenaryMarket(int $kingdomId): array
    {
        $this->db->beginTransaction();
        try {
            $data = $this->getUpgradeData($kingdomId);
            $kingdom = $data['kingdom'];
            $mercenaryMarketConfig = $data['mercenary_market_config'];

            $currentLevel = $kingdom['mercenary_market_level'];
            $nextLevel = $currentLevel + 1;

            if ($nextLevel > $mercenaryMarketConfig['max_level']) {
                throw new Exception("Mercenary Market is already at max level.");
            }

            $cost = $mercenaryMarketConfig['levels'][$nextLevel]['cost'];

            if ($kingdom['gold'] < $cost) {
                throw new Exception("Insufficient gold to upgrade Mercenary Market.");
            }

            $unitsGranted = $mercenaryMarketConfig['levels'][$nextLevel];

            $updateStmt = $this->db->prepare(
                "UPDATE kingdoms SET 
                    gold = gold - :cost,
                    mercenary_market_level = :new_level,
                    unit_guards = unit_guards + :guards,
                    unit_soldiers = unit_soldiers + :soldiers,
                    unit_spies = unit_spies + :spies,
                    unit_sentries = unit_sentries + :sentries
                 WHERE id = :id"
            );
            $updateStmt->execute([
                ':cost' => $cost,
                ':new_level' => $nextLevel,
                ':guards' => $unitsGranted['guards'] ?? 0,
                ':soldiers' => $unitsGranted['soldiers'] ?? 0,
                ':spies' => $unitsGranted['spies'] ?? 0,
                ':sentries' => $unitsGranted['sentries'] ?? 0,
                ':id' => $kingdomId,
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => "Mercenary Market upgraded to level {$nextLevel}. You gained new units!"];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
