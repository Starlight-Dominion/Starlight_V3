<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Repositories\Interfaces\ManpowerRepositoryInterface;
use sdo\Repositories\Interfaces\LogRepositoryInterface;
use sdo\Repositories\Interfaces\AdminLogRepositoryInterface;
use sdo\Repositories\Interfaces\RecruitmentLogRepositoryInterface;
use DateTime;
use DateTimeZone;

class AdminSystemService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private DominionRepositoryInterface $dominionRepository,
        private ManpowerRepositoryInterface $manpowerRepository,
        private LogRepositoryInterface $logRepository,
        private AdminLogRepositoryInterface $adminLogRepository,
        private RecruitmentLogRepositoryInterface $recruitmentLogRepository
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
        $this->adminLogRepository->log([
            'admin_id' => $adminId,
            'action' => strtoupper($action),
            'description' => $description,
            'metadata' => $metadata
        ]);
    }

    public function getPaginatedAdminLogs(int $page = 1, int $perPage = 50, array $filters = []): array
    {
        return $this->adminLogRepository->getPaginatedLogs($page, $perPage, $filters);
    }

    public function getPaginatedRecruitmentLogs(int $page = 1, int $perPage = 50, array $filters = []): array
    {
        return $this->recruitmentLogRepository->getPaginatedLogs($page, $perPage, $filters);
    }

    public function getAuditLogs(int $limit = 100): array
    {
        return $this->logRepository->getAuditLogs($limit);
    }

    public function getRecentBattleLogs(int $limit = 50): array
    {
        return $this->logRepository->getRecentBattleLogs($limit);
    }

    public function getPaginatedGameLogs(int $page = 1, int $perPage = 50, array $filters = []): array
    {
        return $this->logRepository->getPaginatedLogs($page, $perPage, $filters);
    }
}
