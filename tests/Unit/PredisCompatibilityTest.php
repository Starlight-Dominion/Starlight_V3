<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Predis\Client;

class PredisCompatibilityTest extends TestCase
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
            $this->markTestSkipped('Redis not available for Predis compatibility test: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->redis)) {
            $this->redis->disconnect();
        }

        parent::tearDown();
    }

    public function testStringCommandSemanticsUsedBySessionAndRateLimitCode(): void
    {
        $base = 'test:predis:compat:' . bin2hex(random_bytes(8));
        $sessionKey = $base . ':session';
        $counterKey = $base . ':counter';

        $setResult = $this->redis->setex($sessionKey, 30, 'payload');
        $this->assertSame('OK', (string)$setResult);
        $this->assertSame('payload', $this->redis->get($sessionKey));

        $ttl = $this->redis->ttl($sessionKey);
        $this->assertIsInt($ttl);
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(30, $ttl);

        $this->assertSame(1, $this->redis->incr($counterKey));
        $this->assertSame(2, $this->redis->incr($counterKey));
        $this->assertSame(1, $this->redis->expire($counterKey, 60));
        $this->assertSame('2', $this->redis->get($counterKey));

        $this->assertSame(1, $this->redis->del($sessionKey));
        $this->assertNull($this->redis->get($sessionKey));

        $this->redis->del([$counterKey]);
    }

    public function testStreamCommandResponseShapeUsedByActionWorker(): void
    {
        $stream = 'test:predis:stream:' . bin2hex(random_bytes(8));
        $envelope = ['type' => 'link.request', 'sdo_user_id' => '42'];

        $entryId = $this->redis->executeRaw([
            'XADD',
            $stream,
            '*',
            'data',
            json_encode($envelope, JSON_UNESCAPED_SLASHES),
        ]);

        $this->assertIsString($entryId);
        $this->assertNotSame('', $entryId);

        $response = $this->redis->xread(10, 0, [$stream], '0-0');
        $this->assertIsArray($response);
        $this->assertArrayHasKey($stream, $response);
        $this->assertIsArray($response[$stream]);
        $this->assertNotEmpty($response[$stream]);

        $first = $response[$stream][0] ?? null;
        $this->assertIsArray($first);
        $this->assertCount(2, $first);

        $firstId = $first[0] ?? null;
        $this->assertIsString($firstId);
        $this->assertNotSame('', $firstId);

        $rawFields = $first[1] ?? null;
        $this->assertIsArray($rawFields);

        $fields = [];
        for ($i = 0; $i + 1 < count($rawFields); $i += 2) {
            $key = $rawFields[$i];
            if (is_string($key) && $key !== '') {
                $fields[$key] = $rawFields[$i + 1];
            }
        }

        $this->assertArrayHasKey('data', $fields);

        $decoded = json_decode((string)$fields['data'], true);
        $this->assertIsArray($decoded);
        $this->assertSame('link.request', $decoded['type'] ?? null);
        $this->assertSame('42', $decoded['sdo_user_id'] ?? null);

        $this->redis->del([$stream]);
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
