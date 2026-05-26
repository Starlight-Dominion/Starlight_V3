<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Predis\Client;
use sdo\Services\RateLimitService;

class RateLimitServiceRedisTest extends TestCase
{
    private Client $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $config = $this->redisConfig();

        $this->redis = new Client([
            'host' => $config['host'],
            'port' => $config['port'],
            'timeout' => 1,
            'read_write_timeout' => 1,
        ]);

        try {
            $this->redis->ping();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Redis not available for integration test: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->redis)) {
            $this->redis->disconnect();
        }

        parent::tearDown();
    }

    public function testRateLimitAllowsUpToLimitThenBlocks(): void
    {
        $apiKeyId = random_int(100000, 999999);
        $key = $this->currentWindowKey($apiKeyId);
        $this->redis->del([$key]);

        $service = new RateLimitService($this->redis);

        $this->assertFalse($service->isRateLimited($apiKeyId, 2));
        $this->assertFalse($service->isRateLimited($apiKeyId, 2));
        $this->assertTrue($service->isRateLimited($apiKeyId, 2));

        $this->assertSame(2, $service->getCurrentCount($apiKeyId));

        $ttl = $this->redis->ttl($key);
        $this->assertIsInt($ttl);
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(60, $ttl);
    }

    public function testNonPositiveLimitAlwaysBlocks(): void
    {
        $apiKeyId = random_int(100000, 999999);
        $key = $this->currentWindowKey($apiKeyId);
        $this->redis->del([$key]);

        $service = new RateLimitService($this->redis);

        $this->assertTrue($service->isRateLimited($apiKeyId, 0));
        $this->assertTrue($service->isRateLimited($apiKeyId, -5));
        $this->assertSame(0, $service->getCurrentCount($apiKeyId));
    }

    private function currentWindowKey(int $apiKeyId): string
    {
        return "api:rate:{$apiKeyId}:" . date('YmdHi');
    }

    /**
     * @return array{host: string, port: int}
     */
    private function redisConfig(): array
    {
        $redisUrl = getenv('REDIS_URL') ?: ($_ENV['REDIS_URL'] ?? '');
        if (is_string($redisUrl) && $redisUrl !== '') {
            $parts = parse_url($redisUrl);
            if (is_array($parts) && isset($parts['host'])) {
                return [
                    'host' => (string)$parts['host'],
                    'port' => (int)($parts['port'] ?? 6379),
                ];
            }
        }

        return [
            'host' => (string)(getenv('REDIS_HOST') ?: ($_ENV['REDIS_HOST'] ?? '127.0.0.1')),
            'port' => (int)(getenv('REDIS_PORT') ?: ($_ENV['REDIS_PORT'] ?? 6379)),
        ];
    }
}
