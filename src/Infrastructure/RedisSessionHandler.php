<?php

declare(strict_types=1);

namespace sdo\Infrastructure;

use Predis\Client;
use SessionHandlerInterface;

class RedisSessionHandler implements SessionHandlerInterface
{
    private Client $redis;
    private int $ttl;

    public function __construct(Client $redis, int $ttl = 3600)
    {
        $this->redis = $redis;
        $this->ttl = $ttl;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        return $this->redis->get("session:$id") ?? '';
    }

    public function write(string $id, string $data): bool
    {
        $this->redis->setex("session:$id", $this->ttl, $data);
        return true;
    }

    public function destroy(string $id): bool
    {
        $this->redis->del("session:$id");
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return 0; // Redis handles TTL automatically
    }
}
