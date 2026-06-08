<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use sdo\Models\RecruitmentLog;

interface RecruitmentLogRepositoryInterface
{
    public function log(array $data): void;
    public function getPaginatedLogs(int $page = 1, int $perPage = 50, array $filters = []): array;
}
