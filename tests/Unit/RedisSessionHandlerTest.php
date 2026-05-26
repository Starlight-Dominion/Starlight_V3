<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Predis\Client;
use sdo\Infrastructure\RedisSessionHandler;

class RedisSessionHandlerTest extends TestCase
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

    public function testWriteReadAndDestroyRoundTrip(): void
    {
        $handler = new RedisSessionHandler($this->redis, 5);
        $sessionId = 'test-session-' . bin2hex(random_bytes(8));
        $key = "session:$sessionId";

        $this->assertTrue($handler->open('', 'PHPSESSID'));
        $this->assertTrue($handler->write($sessionId, 'payload-data'));
        $this->assertSame('payload-data', $handler->read($sessionId));

        $ttl = $this->redis->ttl($key);
        $this->assertIsInt($ttl);
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(5, $ttl);

        $this->assertTrue($handler->destroy($sessionId));
        $this->assertSame('', $handler->read($sessionId));
        $this->assertTrue($handler->close());
    }

    public function testGcReturnsZeroBecauseRedisHandlesExpiry(): void
    {
        $handler = new RedisSessionHandler($this->redis, 5);

        $this->assertSame(0, $handler->gc(3600));
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
