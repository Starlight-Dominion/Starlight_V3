<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\ManpowerRepositoryInterface;
use sdo\Repositories\Interfaces\LogRepositoryInterface;
use DateTime;
use DateTimeZone;

class AdminSystemService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private DominionRepositoryInterface $dominionRepository,
        private ManpowerRepositoryInterface $manpowerRepository,
        private LogRepositoryInterface $logRepository
    ) {}

    public function getSystemStats(): array
    {
        $playerCount = $this->userRepository->count();
        $kingdomCount = $this->dominionRepository->count();
        $totalCredits = $this->dominionRepository->sum('credits');
        $totalCitizens = $this->dominionRepository->sum('citizens');
        $totalManpower = $this->manpowerRepository->sumTotalQuantity();
        
        return [
            'total_users' => $playerCount,
            'total_kingdoms' => $kingdomCount,
            'total_credits' => $totalCredits,
            'total_citizens' => $totalCitizens,
            'total_manpower' => $totalManpower,
            'server_time' => (new DateTime('now', new DateTimeZone('America/New_York')))->format('H:i:s T')
        ];
    }

    public function logAdminAction(int $adminId, string $action, string $description, array $metadata = []): void
    {
        $this->logRepository->log([
            'dominion_id' => $adminId,
            'action' => 'ADMIN_' . strtoupper($action),
            'description' => $description,
            'metadata' => $metadata
        ]);
    }

    public function getAuditLogs(int $limit = 100): array
    {
        return $this->logRepository->getAuditLogs($limit);
    }

    public function getRecentBattleLogs(int $limit = 50): array
    {
        return $this->logRepository->getRecentBattleLogs($limit);
    }
}
