<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\ApiKey;
use sdo\Models\ApiLog;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class ApiService
{
    /**
     * Authenticate an API token and return the key model.
     */
    public function authenticate(string $token): ?ApiKey
    {
        return ApiKey::where('api_token', $token)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Log an API request.
     */
    public function logRequest(
        ?int $apiKeyId,
        string $endpoint,
        string $method,
        string $ip,
        int $statusCode,
        int $responseTimeMs
    ): void {
        ApiLog::create([
            'api_key_id' => $apiKeyId,
            'endpoint' => $endpoint,
            'method' => $method,
            'ip_address' => $ip,
            'status_code' => $statusCode,
            'response_time_ms' => $responseTimeMs,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Issue a new API key for a user.
     */
    public function issueKey(int $userId, int $rateLimit = 60): ApiKey
    {
        return ApiKey::create([
            'user_id' => $userId,
            'api_token' => bin2hex(random_bytes(32)),
            'rate_limit_per_minute' => $rateLimit,
            'is_active' => true
        ]);
    }

    /**
     * Get all API keys with user metadata.
     */
    public function getAllKeys(): array
    {
        return ApiKey::with('user')->orderBy('created_at', 'desc')->get()->toArray();
    }

    /**
     * Update a key's rate limit or status.
     */
    public function updateKey(int $id, array $data): bool
    {
        $key = ApiKey::findOrFail($id);
        return $key->update($data);
    }

    /**
     * Delete an API key.
     */
    public function deleteKey(int $id): bool
    {
        return ApiKey::where('id', $id)->delete() > 0;
    }

    /**
     * Get recent API logs.
     */
    public function getRecentLogs(int $limit = 100): array
    {
        return ApiLog::with('apiKey.user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
