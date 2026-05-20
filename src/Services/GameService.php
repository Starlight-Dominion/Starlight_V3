<?php
declare(strict_types=1);

namespace sdo\Services;

use DateTime;
use DateTimeZone;
use sdo\Models\Dominion;
use sdo\Repositories\Interfaces\KingdomRepositoryInterface;

class GameService
{
    public const TICK_INTERVAL_SECONDS = 600; 
    public const TIMEZONE = 'America/New_York';

    public function __construct(
        private KingdomRepositoryInterface $kingdomRepository
    ) {}

    public function getRealmTime(): DateTime
    {
        return new DateTime('now', new DateTimeZone(self::TIMEZONE));
    }

    public function getSecondsToNextTick(): int
    {
        $now = $this->getRealmTime();
        $secondsSinceHour = ($now->getTimestamp() % 3600);
        $secondsIntoCurrentTick = $secondsSinceHour % self::TICK_INTERVAL_SECONDS;

        return self::TICK_INTERVAL_SECONDS - $secondsIntoCurrentTick;
    }

    /**
     * Maps to User -> Dominion relation
     */
    public function getKingdomByUserId(int $userId): ?Dominion
    {
        return Dominion::with(['user', 'race'])->where('user_id', $userId)->first();
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
}