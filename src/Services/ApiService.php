<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\ApiKey;
use sdo\Models\ApiLog;
use sdo\Models\ApiApplication;
use sdo\Repositories\Interfaces\ApiRepositoryInterface;
use sdo\Infrastructure\TransactionManager;
use Exception;

class ApiService
{
    public function __construct(
        private ApiRepositoryInterface $apiRepository,
        private TransactionManager $transactionManager
    ) {}

    /**
     * Authenticate an API token and return the key model.
     */
    public function authenticate(string $token): ?ApiKey
    {
        return $this->apiRepository->findKeyByToken($token);
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

        $this->apiRepository->createLog([
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
        if ($this->apiRepository->hasPendingApplication($userId)) {
            throw new Exception("You already have an active request pending review by High Command.");
        }

        $this->apiRepository->createApplication([
            'user_id' => $userId,
            'project_name' => $projectName,
            'justification' => $justification,
            'status' => 'pending'
        ]);

        return ['success' => true, 'message' => "Request transmitted. Awaiting High Command authorization."];
    }

    /**
     * Get the user's latest application status.
     */
    public function getUserApplication(int $userId): ?ApiApplication
    {
        return $this->apiRepository->getLatestApplicationByUser($userId);
    }

    /**
     * Get all API keys for a specific user.
     */
    public function getUserKeys(int $userId): array
    {
        return $this->apiRepository->getKeysByUser($userId);
    }

    /**
     * Get pending applications for Admin review.
     */
    public function getPendingApplications(): array
    {
        return $this->apiRepository->getPendingApplications();
    }

    /**
     * Process an application (Approve/Reject).
     */
    public function processApplication(int $appId, string $action, int $rateLimit = 60, string $notes = ''): array
    {
        return $this->transactionManager->transaction(function() use ($appId, $action, $rateLimit, $notes) {
            $app = $this->apiRepository->lockApplicationForUpdate($appId);
            if (!$app || $app->status !== 'pending') {
                throw new Exception("Application not found or already processed.");
            }

            if ($action === 'approve') {
                $this->issueKey((int)$app->user_id, $rateLimit);
                $status = 'approved';
            } else {
                $status = 'rejected';
            }

            $this->apiRepository->updateApplication($appId, [
                'status' => $status,
                'admin_notes' => $notes
            ]);

            return ['success' => true, 'message' => "Application $status."];
        });
    }

    /**
     * Issue a new API key for a user.
     */
    public function issueKey(int $userId, int $rateLimit = 60, string $scopes = '*'): ApiKey
    {
        return $this->apiRepository->createKey([
            'user_id' => $userId,
            'api_token' => bin2hex(random_bytes(32)),
            'rate_limit_per_minute' => $rateLimit,
            'scopes' => trim($scopes) !== '' ? trim($scopes) : '*',
            'is_active' => true
        ]);
    }

    /**
     * Get all API keys with user metadata.
     */
    public function getAllKeys(): array
    {
        return $this->apiRepository->getAllKeys();
    }

    /**
     * Update a key's rate limit or status.
     */
    public function updateKey(int $id, array $data): bool
    {
        return $this->apiRepository->updateKey($id, $data);
    }

    /**
     * Delete an API key.
     */
    public function deleteKey(int $id): bool
    {
        return $this->apiRepository->deleteKey($id);
    }

    /**
     * Get recent API logs.
     */
    public function getRecentLogs(int $limit = 100): array
    {
        return $this->apiRepository->getRecentLogs($limit);
    }
}
