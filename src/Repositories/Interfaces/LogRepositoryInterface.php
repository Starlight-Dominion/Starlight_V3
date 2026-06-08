<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

interface LogRepositoryInterface
{
    public function log(array $data): void;
    public function getAuditLogs(int $limit = 100): array;
    public function getRecentBattleLogs(int $limit = 50): array;
    public function getPaginatedLogs(int $page = 1, int $perPage = 50, array $filters = []): array;
}
