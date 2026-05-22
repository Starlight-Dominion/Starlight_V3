<?php
declare(strict_types=1);

namespace sdo\Infrastructure;

use sdo\Services\ApiService;
use sdo\Services\RateLimitService;
use sdo\Models\ApiKey;
use Exception;

class ApiAuthMiddleware
{
    public function __construct(
        private ApiService $apiService,
        private RateLimitService $rateLimitService
    ) {}

    /**
     * Authenticate the request and check rate limits.
     * Throws exception on failure.
     */
    public function handle(): ApiKey
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw new Exception("Unauthorized: Bearer token required.", 401);
        }

        $token = substr($authHeader, 7);
        $apiKey = $this->apiService->authenticate($token);

        if (!$apiKey) {
            throw new Exception("Unauthorized: Invalid or inactive API token.", 401);
        }

        if ($this->rateLimitService->isRateLimited($apiKey->id, $apiKey->rate_limit_per_minute)) {
            throw new Exception("Too Many Requests: Rate limit exceeded.", 429);
        }

        return $apiKey;
    }
}
