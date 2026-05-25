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
    public function handle(?string $requiredScope = null): ApiKey
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

        if (!$this->hasScope($apiKey, $requiredScope)) {
            throw new Exception("Forbidden: API token is missing required scope.", 403);
        }

        if ($this->rateLimitService->isRateLimited($apiKey->id, $apiKey->rate_limit_per_minute)) {
            throw new Exception("Too Many Requests: Rate limit exceeded.", 429);
        }

        return $apiKey;
    }

    private function hasScope(ApiKey $apiKey, ?string $requiredScope): bool
    {
        $requiredScope = trim((string)$requiredScope);
        if ($requiredScope === '') {
            return true;
        }

        $rawScopes = trim((string)($apiKey->scopes ?? '*'));
        if ($rawScopes === '' || $rawScopes === '*') {
            return true;
        }

        $scopes = array_values(array_filter(array_map(
            static fn(string $scope): string => trim($scope),
            explode(',', $rawScopes)
        )));

        foreach ($scopes as $scope) {
            if ($scope === '*') {
                return true;
            }
            if ($scope === $requiredScope) {
                return true;
            }
            if (str_ends_with($scope, '.*')) {
                $prefix = substr($scope, 0, -2);
                if ($prefix !== '' && str_starts_with($requiredScope, $prefix . '.')) {
                    return true;
                }
            }
        }

        return false;
    }
}
