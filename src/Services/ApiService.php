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
     * Log an API request with high-fidelity telemetry.
     */
    public function logRequest(
        ?int $apiKeyId,
        string $endpoint,
        string $method,
        string $ip,
        int $statusCode,
        int $responseTimeMs,
        ?string $userAgent = null,
        ?array $payload = null,
        ?string $errorLog = null
    ): void {
        // Sanitize payload: remove sensitive data if any
        if ($payload) {
            unset($payload['password'], $payload['cipher'], $payload['token']);
        }

        ApiLog::create([
            'api_key_id' => $apiKeyId,
            'endpoint' => $endpoint,
            'method' => $method,
            'ip_address' => $ip,
            'status_code' => $statusCode,
            'response_time_ms' => $responseTimeMs,
            'user_agent' => $userAgent,
            'payload' => $payload ? json_encode($payload) : null,
            'error_log' => $errorLog,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Submit a new API access application.
     */
    public function submitApplication(int $userId, string $projectName, string $justification): array
    {
        // Check for existing pending application
        $existing = Capsule::table('api_applications')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            throw new Exception("You already have an active request pending review by High Command.");
        }

        Capsule::table('api_applications')->insert([
            'user_id' => $userId,
            'project_name' => $projectName,
            'justification' => $justification,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return ['success' => true, 'message' => "Request transmitted. Awaiting High Command authorization."];
    }

    /**
     * Get the user's latest application status.
     */
    public function getUserApplication(int $userId): ?object
    {
        return Capsule::table('api_applications')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get all API keys for a specific user.
     */
    public function getUserKeys(int $userId): array
    {
        return ApiKey::where('user_id', $userId)->get()->toArray();
    }

    /**
     * Get pending applications for Admin review.
     */
    public function getPendingApplications(): array
    {
        return Capsule::table('api_applications')
            ->join('users', 'api_applications.user_id', '=', 'users.id')
            ->where('status', 'pending')
            ->select('api_applications.*', 'users.username')
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Process an application (Approve/Reject).
     */
    public function processApplication(int $appId, string $action, int $rateLimit = 60, string $notes = ''): array
    {
        return Capsule::transaction(function() use ($appId, $action, $rateLimit, $notes) {
            $app = Capsule::table('api_applications')->where('id', $appId)->first();
            if (!$app || $app->status !== 'pending') {
                throw new Exception("Application not found or already processed.");
            }

            if ($action === 'approve') {
                $this->issueKey((int)$app->user_id, $rateLimit);
                $status = 'approved';
            } else {
                $status = 'rejected';
            }

            Capsule::table('api_applications')
                ->where('id', $appId)
                ->update([
                    'status' => $status,
                    'admin_notes' => $notes,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

            return ['success' => true, 'message' => "Application $status."];
        });
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
