<?php
declare(strict_types=1);

namespace sdo\Services;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use sdo\Models\Dominion;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\ManpowerRepositoryInterface;
use sdo\Repositories\Interfaces\DominionStructureRepositoryInterface;
use sdo\Services\ConfigService;

class GameService
{
    private const ALLOWED_STRUCTURE_LEVEL_BUFF_COLUMNS = [
        'buff_citizens_per_tick',
        'buff_economy',
    ];

    public const TIMEZONE = 'America/New_York';
    public const BASE_INCOME = 5000;
    public const CREDITS_PER_WORKER = 50;
    public const BASE_CITIZENS = 1;

    public const BASE_TURNS_PER_TICK = 4;

    public function __construct(
        private ConfigService $configService,
        private DominionRepositoryInterface $dominionRepository,
        private ManpowerRepositoryInterface $manpowerRepository,
        private DominionStructureRepositoryInterface $dominionStructureRepository,
        private \sdo\Repositories\Interfaces\RecruitmentRepositoryInterface $recruitmentRepository
    ) {}

    /**
     * Checks if the dominion has any available recruitment capacity (active session or remaining daily sessions).
     */
    public function hasAvailableRecruitment(int $dominionId): bool
    {
        $active = $this->recruitmentRepository->findActiveSession($dominionId);
        if ($active) {
            return true;
        }

        $dailyLimit = (int)$this->configService->get('recruitment_sessions_per_day', 2);
        $dailyCount = $this->recruitmentRepository->countRecentSessions($dominionId, 24);

        $threeDayLimit = (int)$this->configService->get('recruitment_sessions_per_3days', 5);
        $threeDayCount = $this->recruitmentRepository->countRecentSessions($dominionId, 72);

        return $dailyCount < $dailyLimit && $threeDayCount < $threeDayLimit;
    }

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
        return $this->dominionRepository->findByUserId($userId);
    }

    public function getDominionById(int $id): ?Dominion
    {
        return $this->dominionRepository->findById($id);
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

        if ($nextThreshold <= $currentThreshold) return 0;

        $progress = (($xp - $currentThreshold) / ($nextThreshold - $currentThreshold)) * 100;
        return (int)max(0, min(100, $progress));
    }

    /**
     * Calculates the total credit income per tick including multipliers.
     */
    public function getTotalIncome(int $dominionId): int
    {
        return $this->getIncomeBreakdown($dominionId)['total'];
    }

    /**
     * Provides a detailed breakdown of the total credit income.
     * Legacy Formula: (BASE_INCOME + (workers * 50)) * economyMult * wealthMult
     */
    public function getIncomeBreakdown(int $dominionId): array
    {
        $dominion = $this->dominionRepository->findById($dominionId);
        if (!$dominion) {
            return ['base' => 0, 'units' => [], 'unit_total' => 0, 'bonus_percent' => 0, 'total' => 0];
        }

        $baseCredits = self::BASE_INCOME;
        
        $unitProductionList = [];
        $totalUnitProduction = 0;
        
        $manpower = $this->manpowerRepository->getManpowerByDominion($dominionId);
                
        foreach ($manpower as $m) {
            if ($m->total_quantity > 0) {
                // If the unit has a production value, use it. Otherwise, workers use the legacy constant.
                $prodValue = ($m->unit->slug === 'workers') ? self::CREDITS_PER_WORKER : ($m->unit->production_credits ?? 0);
                
                if ($prodValue > 0) {
                    $prod = $m->total_quantity * $prodValue;
                    $totalUnitProduction += $prod;
                    $unitProductionList[] = [
                        'name' => $m->unit->name,
                        'quantity' => $m->total_quantity,
                        'production' => $prod
                    ];
                }
            }
        }

        $economyBuff = (float)$this->sumStructureLevelBuff($dominionId, 'buff_economy');
        $economyMult = 1 + ($economyBuff / 100);

        // Map Charisma to Legacy Wealth multiplier
        $wealthMult = 1 + ($dominion->charisma_points * 0.01);
        
        $totalIncome = (int)floor(($baseCredits + $totalUnitProduction) * $economyMult * $wealthMult);

        return [
            'base' => $baseCredits,
            'units' => $unitProductionList,
            'unit_total' => $totalUnitProduction,
            'bonus_percent' => (int)$economyBuff,
            'wealth_multiplier' => $wealthMult,
            'total' => $totalIncome
        ];
    }

    /**
     * Calculates the total citizen growth per tick including buffs.
     * Legacy Formula: BASE_CITIZENS + populationLevelBonuses
     */
    public function getTotalCitizenGrowth(int $dominionId): int
    {
        $baseCitizens = self::BASE_CITIZENS;
        $totalBuff = (int)$this->sumStructureLevelBuff($dominionId, 'buff_citizens_per_tick');

        return $baseCitizens + $totalBuff;
    }

    /**
     * Calculates the total economic multiplier from all structures.
     */
    public function getEconomyMultiplier(int $dominionId): float
    {
        $totalBuff = $this->sumStructureLevelBuff($dominionId, 'buff_economy');

        return 1 + ($totalBuff / 100);
    }

    private function sumStructureLevelBuff(int $dominionId, string $column): float
    {
        if (!in_array($column, self::ALLOWED_STRUCTURE_LEVEL_BUFF_COLUMNS, true)) {
            throw new InvalidArgumentException('Invalid structure level buff column specified.');
        }

        return $this->dominionStructureRepository->sumStructureLevelBuff($dominionId, $column);
    }
}
