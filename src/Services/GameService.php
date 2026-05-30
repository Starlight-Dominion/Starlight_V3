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
    public const BASE_INCOME = 100;

    public const BASE_TURNS_PER_TICK = 4;

    public function __construct(
        private ConfigService $configService,
        private DominionRepositoryInterface $dominionRepository,
        private ManpowerRepositoryInterface $manpowerRepository,
        private DominionStructureRepositoryInterface $dominionStructureRepository
    ) {}

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
     */
    public function getIncomeBreakdown(int $dominionId): array
    {
        $baseCredits = (int)$this->configService->get('baseline_credits_per_tick', 100);
        
        $unitProductionList = [];
        $totalUnitProduction = 0;
        
        $manpower = $this->manpowerRepository->getManpowerByDominion($dominionId);
                
        foreach ($manpower as $m) {
            if ($m->total_quantity > 0 && ($m->unit->production_credits ?? 0) > 0) {
                $prod = $m->total_quantity * $m->unit->production_credits;
                $totalUnitProduction += $prod;
                $unitProductionList[] = [
                    'name' => $m->unit->name,
                    'quantity' => $m->total_quantity,
                    'production' => $prod
                ];
            }
        }

        $totalBuff = (float)$this->sumStructureLevelBuff($dominionId, 'buff_economy');
        $multiplier = 1 + ($totalBuff / 100);
        
        $totalIncome = (int)floor(($baseCredits + $totalUnitProduction) * $multiplier);

        return [
            'base' => $baseCredits,
            'units' => $unitProductionList,
            'unit_total' => $totalUnitProduction,
            'bonus_percent' => (int)$totalBuff,
            'total' => $totalIncome
        ];
    }

    /**
     * Calculates the total citizen growth per tick including buffs.
     */
    public function getTotalCitizenGrowth(int $dominionId): int
    {
        $baseCitizens = (int)$this->configService->get('baseline_citizens_per_tick', 50);

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
