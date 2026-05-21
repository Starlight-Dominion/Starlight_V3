<?php
declare(strict_types=1);

namespace sdo\Services;

use PDO;
use DateTime;

class TickService
{
    private const BATCH_SIZE = 100;
    private const BASE_CREDITS_PER_TICK = 100;
    private const BASE_CITIZENS_PER_TICK = 50;
    private const BASE_TURNS_PER_TICK = 10;

    public function __construct(private PDO $db) {}

    public function processGlobalTick(): void
    {
        $offset = 0;
        $now = (new DateTime())->format('Y-m-d H:i:s');
        echo "Starting Global Tick at {$now}...\n";

        while (true) {
            $stmt = $this->db->prepare(
                "SELECT d.*, 
                (SELECT SUM(sl.buff_economy) 
                 FROM dominion_structures ds 
                 JOIN structure_levels sl ON ds.structure_id = sl.structure_id AND ds.level = sl.level
                 WHERE ds.dominion_id = d.id) as total_economy_buff
                FROM dominions d 
                LIMIT :limit OFFSET :offset"
            );
            
            $stmt->bindValue(':limit', self::BATCH_SIZE, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $dominions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($dominions)) break;

            $this->db->beginTransaction();

            $updateStmt = $this->db->prepare(
                "UPDATE dominions 
                 SET credits = credits + :credits_gained, 
                     citizens = citizens + :citizens_gained, 
                     turns = LEAST(turns + :turns_gained, 200),
                     last_tick = :now 
                 WHERE id = :id"
            );

            foreach ($dominions as $dom) {
                $multiplier = 1 + ((float)($dom['total_economy_buff'] ?? 0) / 100);
                $creditsGained = (int)floor(self::BASE_CREDITS_PER_TICK * $multiplier);

                $updateStmt->execute([
                    ':credits_gained' => $creditsGained,
                    ':citizens_gained' => self::BASE_CITIZENS_PER_TICK,
                    ':turns_gained'   => self::BASE_TURNS_PER_TICK,
                    ':now'            => $now,
                    ':id'             => $dom['id'],
                ]);
            }

            $this->db->commit();
            $offset += self::BATCH_SIZE;
            echo "Processed " . count($dominions) . " sectors...\n";
        }

        echo "Global Tick completed successfully.\n";
    }
}