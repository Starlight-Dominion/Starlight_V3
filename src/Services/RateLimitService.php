<?php
declare(strict_types=1);

namespace sdo\Services;

use Predis\Client;

class RateLimitService
{
    public function __construct(private Client $redis) {}

    /**
     * Check if a request should be rate limited.
     * Uses a fixed-window counter in Redis (1 minute granularity).
     */
    public function isRateLimited(int $apiKeyId, int $limitPerMinute): bool
    {
        if ($limitPerMinute <= 0) return true;

        $key = "api:rate:{$apiKeyId}:" . date('YmdHi');
        
        $current = $this->redis->get($key);
        
        if ($current !== null && (int)$current >= $limitPerMinute) {
            return true;
        }

        // Increment and set TTL if new
        $count = $this->redis->incr($key);
        if ($count === 1) {
            $this->redis->expire($key, 60);
        }

        return false;
    }

    /**
     * Get the current count for a key in the current minute.
     */
    public function getCurrentCount(int $apiKeyId): int
    {
        $key = "api:rate:{$apiKeyId}:" . date('YmdHi');
        return (int)($this->redis->get($key) ?? 0);
    }
}
