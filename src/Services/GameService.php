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

    public const BASE_TURNS_PER_TICK = 10;

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

    public function getKingdomByUserId(int $userId): ?Dominion
    {
        return Dominion::with(['user', 'race'])->where('user_id', $userId)->first();
    }

    public function getKingdomById(int $id): ?Dominion
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
     * Calculates the total economic multiplier from all structures.
     */
    public function getEconomyMultiplier(int $dominionId): float
    {
        $totalBuff = (float)Capsule::table('dominion_structures')
            ->join('structure_levels', function($join) {
                $join->on('dominion_structures.structure_id', '=', 'structure_levels.structure_id')
                     ->on('dominion_structures.level', '=', 'structure_levels.level');
            })
            ->where('dominion_structures.dominion_id', $dominionId)
            ->sum('structure_levels.buff_economy');

        return 1 + ($totalBuff / 100);
    }
}
