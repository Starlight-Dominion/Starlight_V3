<?php
declare(strict_types=1);

namespace sdo\Services;

use DateTime;
use DateTimeZone;
use sdo\Models\Dominion;
use sdo\Services\ConfigService;
use Illuminate\Database\Capsule\Manager as Capsule;

class GameService
{
    public const TIMEZONE = 'America/New_York';
    public const BASE_INCOME = 100;

    public const BASE_TURNS_PER_TICK = 4;

    public function __construct(private ConfigService $configService) {}

    public function getRealmTime(): DateTime
    {
        return new DateTime('now', new DateTimeZone(self::TIMEZONE));
    }

    public function getSecondsToNextTick(): int
    {
        $interval = (int)$this->configService->get('tick_interval_seconds', 900);
        $now = $this->getRealmTime();
        
        // Use a 3600 second hour base for consistency
        $secondsSinceHour = ($now->getTimestamp() % 3600);
        $secondsIntoCurrentTick = $secondsSinceHour % $interval;

        return $interval - $secondsIntoCurrentTick;
    }

    public function getDominionByUserId(int $userId): ?Dominion
    {
        return Dominion::with(['user', 'race'])->where('user_id', $userId)->first();
    }

    public function getDominionById(int $id): ?Dominion
    {
        return Dominion::with(['user', 'race'])->find($id);
    }

    public function calculateXpProgress(int $xp): int
    {
        $level = (int)floor(sqrt($xp / 100)) + 1;
        $currentThreshold = (int)pow($level - 1, 2) * 100;
        $nextThreshold = (int)pow($level, 2) * 100;

        if ($nextThreshold <= $currentThreshold) return 0;

        $progress = (($xp - $currentThreshold) / ($nextThreshold - $currentThreshold)) * 100;
        return (int)max(0, min(100, $progress));
    }

    /**
     * Calculates the total credit income per tick including multipliers.
     */
    public function getTotalIncome(int $dominionId): int
    {
        $baseCredits = (int)$this->configService->get('baseline_credits_per_tick', 100);
        
        // Defensive check: Verify column exists before querying
        static $hasProductionColumn = null;
        if ($hasProductionColumn === null) {
            $hasProductionColumn = Capsule::schema()->hasColumn('units', 'production_credits');
        }

        $unitProduction = 0;
        if ($hasProductionColumn) {
            $unitProduction = (int)\sdo\Models\DominionManpower::join('units', 'dominion_manpower.unit_id', '=', 'units.id')
                ->where('dominion_manpower.dominion_id', $dominionId)
                ->sum(Capsule::raw('dominion_manpower.total_quantity * units.production_credits'));
        }

        $multiplier = $this->getEconomyMultiplier($dominionId);
        
        return (int)floor(($baseCredits + $unitProduction) * $multiplier);
    }

    /**
     * Calculates the total citizen growth per tick including buffs.
     */
    public function getTotalCitizenGrowth(int $dominionId): int
    {
        $baseCitizens = (int)$this->configService->get('baseline_citizens_per_tick', 50);
        
        $totalBuff = (int)\sdo\Models\DominionStructure::join('structure_levels', function($join) {
                $join->on('dominion_structures.structure_id', '=', 'structure_levels.structure_id')
                     ->on('dominion_structures.level', '=', 'structure_levels.level');
            })
            ->where('dominion_structures.dominion_id', $dominionId)
            ->sum('structure_levels.buff_citizens_per_tick');

        return $baseCitizens + $totalBuff;
    }

    /**
     * Calculates the total economic multiplier from all structures.
     */
    public function getEconomyMultiplier(int $dominionId): float
    {
        $totalBuff = (float)\sdo\Models\DominionStructure::join('structure_levels', function($join) {
                $join->on('dominion_structures.structure_id', '=', 'structure_levels.structure_id')
                     ->on('dominion_structures.level', '=', 'structure_levels.level');
            })
            ->where('dominion_structures.dominion_id', $dominionId)
            ->sum('structure_levels.buff_economy');

        return 1 + ($totalBuff / 100);
    }
}
