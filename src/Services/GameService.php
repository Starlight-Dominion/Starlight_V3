<?php

declare(strict_types=1);

namespace sdo\Services;

use DateTime;
use DateTimeZone;
use sdo\Models\Kingdom;
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

    public function getKingdomByUserId(int $userId): ?Kingdom
    {
        return $this->kingdomRepository->findByUserId($userId);
    }

    public function getKingdomById(int $id): ?Kingdom
    {
        return $this->kingdomRepository->findById($id);
    }

    public function calculateLevel(int $xp): int
    {
        return (int)floor(sqrt($xp / 100)) + 1;
    }

    public function calculateXpProgress(int $xp): int
    {
        $level = $this->calculateLevel($xp);
        $currentThreshold = (int)pow($level - 1, 2) * 100;
        $nextThreshold = (int)pow($level, 2) * 100;

        if ($nextThreshold <= $currentThreshold) {
            return 0;
        }

        $progress = (($xp - $currentThreshold) / ($nextThreshold - $currentThreshold)) * 100;
        return (int)max(0, min(100, $progress));
    }
}