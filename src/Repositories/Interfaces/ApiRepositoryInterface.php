<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use sdo\Models\ApiKey;
use sdo\Models\ApiLog;
use sdo\Models\ApiApplication;
use Illuminate\Support\Collection;

interface ApiRepositoryInterface
{
    // API Keys
    public function findKeyByToken(string $token): ?ApiKey;
    public function findKeyById(int $id): ?ApiKey;
    public function getKeysByUser(int $userId): array;
    public function getAllKeys(): array;
    public function createKey(array $data): ApiKey;
    public function updateKey(int $id, array $data): bool;
    public function deleteKey(int $id): bool;

    // API Logs
    public function createLog(array $data): ApiLog;
    public function getRecentLogs(int $limit = 100): array;

    // API Applications
    public function findApplicationById(int $id): ?ApiApplication;
    public function lockApplicationForUpdate(int $id): ?ApiApplication;
    public function getLatestApplicationByUser(int $userId): ?ApiApplication;
    public function getPendingApplications(): array;
    public function hasPendingApplication(int $userId): bool;
    public function createApplication(array $data): ApiApplication;
    public function updateApplication(int $id, array $data): bool;
}
