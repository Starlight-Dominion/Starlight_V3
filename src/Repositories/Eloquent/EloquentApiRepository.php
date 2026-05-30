<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\ApiKey;
use sdo\Models\ApiLog;
use sdo\Models\ApiApplication;
use sdo\Repositories\Interfaces\ApiRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentApiRepository implements ApiRepositoryInterface
{
    public function findKeyByToken(string $token): ?ApiKey
    {
        return ApiKey::where('api_token', $token)
            ->where('is_active', true)
            ->first();
    }

    public function findKeyById(int $id): ?ApiKey
    {
        return ApiKey::find($id);
    }

    public function getKeysByUser(int $userId): array
    {
        return ApiKey::where('user_id', $userId)->get()->toArray();
    }

    public function getAllKeys(): array
    {
        return ApiKey::with('user')->orderBy('created_at', 'desc')->get()->toArray();
    }

    public function createKey(array $data): ApiKey
    {
        return ApiKey::create($data);
    }

    public function updateKey(int $id, array $data): bool
    {
        $key = ApiKey::find($id);
        return $key ? $key->update($data) : false;
    }

    public function deleteKey(int $id): bool
    {
        return ApiKey::where('id', $id)->delete() > 0;
    }

    public function createLog(array $data): ApiLog
    {
        return ApiLog::create($data);
    }

    public function getRecentLogs(int $limit = 100): array
    {
        return ApiLog::with('apiKey.user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function findApplicationById(int $id): ?ApiApplication
    {
        return ApiApplication::find($id);
    }

    public function lockApplicationForUpdate(int $id): ?ApiApplication
    {
        return ApiApplication::lockForUpdate()->find($id);
    }

    public function getLatestApplicationByUser(int $userId): ?ApiApplication
    {
        return ApiApplication::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function getPendingApplications(): array
    {
        return ApiApplication::with('user')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    public function hasPendingApplication(int $userId): bool
    {
        return ApiApplication::where('user_id', $userId)
            ->where('status', 'pending')
            ->exists();
    }

    public function createApplication(array $data): ApiApplication
    {
        return ApiApplication::create($data);
    }

    public function updateApplication(int $id, array $data): bool
    {
        $app = ApiApplication::find($id);
        return $app ? $app->update($data) : false;
    }
}
