<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Repositories\Interfaces\TickRepositoryInterface;
use sdo\Infrastructure\TransactionManager;
use sdo\Services\GameService;
use sdo\Services\ConfigService;
use Predis\Client as Redis;
use DateTime;

class TickService
{
    public const BATCH_SIZE = 100;
    public const STREAM_KEY = 'sdo:tick:jobs';
    public const METRICS_KEY_PREFIX = 'sdo:tick:metrics:';

    public function __construct(
        private ConfigService $configService,
        private Redis $redis,
        private TickRepositoryInterface $tickRepository,
        private TransactionManager $transactionManager
    ) {}

    /**
     * Dispatcher: Fetches all dominion IDs and pushes chunks to Redis Stream.
     */
    public function dispatchTickJobs(): void
    {
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $tickId = bin2hex(random_bytes(8));
        $metricsKey = self::METRICS_KEY_PREFIX . $tickId;

        $dominionIds = $this->tickRepository->getAllDominionIds();
        $totalSectors = count($dominionIds);
        $chunks = array_chunk($dominionIds, self::BATCH_SIZE);

        foreach ($chunks as $chunk) {
            $payload = json_encode([
                'tick_id' => $tickId,
                'tick_time' => $now,
                'dominion_ids' => $chunk
            ]);
            
            $this->redis->executeRaw(['XADD', self::STREAM_KEY, '*', 'data', $payload]);
        }

        // Initialize metrics in Redis
        $this->redis->hset($metricsKey, 'total_sectors', (string)$totalSectors);
        $this->redis->hset($metricsKey, 'total_credits', '0');
        $this->redis->hset($metricsKey, 'total_citizens', '0');
        $this->redis->hset($metricsKey, 'total_turns', '0');
        $this->redis->expire($metricsKey, 3600); // 1 hour TTL

        // Create initial TickLog entry
        $this->tickRepository->createTickLog([
            'tick_time' => $now,
            'total_sectors' => $totalSectors,
            'total_credits_granted' => 0,
            'total_citizens_born' => 0,
            'total_turns_granted' => 0,
            'execution_time_ms' => 0,
            'metadata' => [
                'tick_id' => $tickId,
                'status' => 'dispatched',
                'batch_size' => self::BATCH_SIZE,
                'chunk_count' => count($chunks)
            ]
        ]);
    }

    /**
     * Processor: Processes a specific chunk of dominions.
     */
    public function processTickJob(array $dominionIds, string $tickTime, string $tickId): void
    {
        $metricsKey = self::METRICS_KEY_PREFIX . $tickId;

        $baseCitizens = (int)$this->configService->get('baseline_citizens_per_tick', 50);
        $baseCredits = (int)$this->configService->get('baseline_credits_per_tick', 100);
        $baseTurns = GameService::BASE_TURNS_PER_TICK;

        $localMetrics = [
            'credits' => 0,
            'citizens' => 0,
            'turns' => 0
        ];

        $this->transactionManager->transaction(function () use ($dominionIds, $baseCitizens, $baseCredits, $baseTurns, $tickTime, &$localMetrics) {
            $localMetrics = $this->tickRepository->applyTickResultsSetBased(
                $dominionIds,
                $baseCredits,
                $baseCitizens,
                $baseTurns,
                $tickTime
            );
        });

        // Atomically update Redis metrics
        $this->redis->hincrby($metricsKey, 'total_credits', $localMetrics['credits']);
        $this->redis->hincrby($metricsKey, 'total_citizens', $localMetrics['citizens']);
        $this->redis->hincrby($metricsKey, 'total_turns', $localMetrics['turns']);
    }

    /**
     * Aggregator: Pulls metrics from Redis and finalizes the TickLog record.
     */
    public function finalizeTickLog(string $tickId, float $executionTimeMs): void
    {
        $metricsKey = self::METRICS_KEY_PREFIX . $tickId;
        $data = $this->redis->hgetall($metricsKey);

        if (empty($data)) {
            return;
        }

        $log = $this->tickRepository->findTickLogByTickId($tickId);
        if ($log) {
            $meta = $log->metadata;
            $meta['status'] = 'completed';

            $this->tickRepository->updateTickLogByTickId($tickId, [
                'total_credits_granted' => (int)($data['total_credits'] ?? 0),
                'total_citizens_born' => (int)($data['total_citizens'] ?? 0),
                'total_turns_granted' => (int)($data['total_turns'] ?? 0),
                'execution_time_ms' => $executionTimeMs,
                'metadata' => $meta
            ]);
        }

        $this->redis->del($metricsKey);
    }

    /**
     * Legacy support / wrapper for unit tests.
     */
    public function processGlobalTick(): void
    {
        $this->dispatchTickJobs();
    }
}
