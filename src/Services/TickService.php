<?php

namespace sdo\Services;

use PDO;
use DateTime;

class TickService
{
    private PDO $db;
    private UntrainingService $untrainingService;
    private MinesService $minesService;
    private array $housingConfig;
    private const BATCH_SIZE = 100;
    private const BASE_CITIZENS_PER_TICK = 50; // Defined as class constant
    private const BASE_TURNS_PER_TICK = 10;   // Defined as class constant

    public function __construct(PDO $db, UntrainingService $untrainingService, MinesService $minesService)
    {
        $this->db = $db;
        $this->untrainingService = $untrainingService;
        $this->minesService = $minesService;
        $this->housingConfig = require __DIR__ . '/../../config/housing.php'; // Load config once
    }

    public function processGlobalTick(): void
    {
        $offset = 0;
        $now = (new DateTime())->format('Y-m-d H:i:s');
        echo "Starting Global Tick at {$now}...
";

        // First, release held citizens for all kingdoms
        $this->untrainingService->releaseHeldCitizens(0); 

        while (true) {
            $stmt = $this->db->prepare(
                "SELECT * FROM kingdoms LIMIT :limit OFFSET :offset"
            );
            $stmt->bindValue(':limit', self::BATCH_SIZE, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $kingdoms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($kingdoms)) {
                break; // No more kingdoms to process
            }
            
            echo "Processing batch of " . count($kingdoms) . " kingdoms...
";

            $this->db->beginTransaction();

            $updateStmt = $this->db->prepare(
                "UPDATE kingdoms 
                 SET gold = gold + :gold_gained, 
                     citizens = citizens + :citizens_gained, 
                     turns = turns + :turns_gained,
                     last_tick = :now 
                 WHERE id = :id"
            );

            foreach ($kingdoms as $kingdom) {
                $goldGained = $kingdom['base_gold_per_tick'] + $this->minesService->calculateCurrentProduction($kingdom);
                
                $citizensGained = $this->housingConfig['levels'][$kingdom['housing_level']]['citizens_per_tick'] ?? self::BASE_CITIZENS_PER_TICK;

                $updateStmt->execute([
                    ':gold_gained' => $goldGained,
                    ':citizens_gained' => $citizensGained,
                    ':turns_gained' => self::BASE_TURNS_PER_TICK, // Corrected constant reference
                    ':now' => $now,
                    ':id' => $kingdom['id'],
                ]);
            }

            $this->db->commit();

            $offset += self::BATCH_SIZE;
        }
        
        echo "Global Tick completed successfully.
";
    }
}
