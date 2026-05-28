<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\Dominion;
use sdo\Models\TickLog;
use sdo\Services\GameService;
use sdo\Services\ConfigService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Predis\Client as Redis;
use DateTime;

class TickService
{
    public const BATCH_SIZE = 100;
    public const STREAM_KEY = 'sdo:tick:jobs';
    public const METRICS_KEY_PREFIX = 'sdo:tick:metrics:';

    public function __construct(
        private ConfigService $configService,
        private Redis $redis
    ) {}

    /**
     * Dispatcher: Fetches all dominion IDs and pushes chunks to Redis Stream.
     */
    public function dispatchTickJobs(): void
    {
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $tickId = bin2hex(random_bytes(8));
        $metricsKey = self::METRICS_KEY_PREFIX . $tickId;

        echo "Dispatching Global Tick [ID: {$tickId}] at {$now}...\n";

        $dominionIds = Dominion::pluck('id')->toArray();
        $totalSectors = count($dominionIds);
        $chunks = array_chunk($dominionIds, self::BATCH_SIZE);

        foreach ($chunks as $chunk) {
            $payload = json_encode([
                'tick_id' => $tickId,
                'tick_time' => $now,
                'dominion_ids' => $chunk
            ]);
            
            // XADD stream * data payload
            $this->redis->executeRaw(['XADD', self::STREAM_KEY, '*', 'data', $payload]);
        }

        // Initialize metrics in Redis
        $this->redis->hset($metricsKey, 'total_sectors', (string)$totalSectors);
        $this->redis->hset($metricsKey, 'total_credits', '0');
        $this->redis->hset($metricsKey, 'total_citizens', '0');
        $this->redis->hset($metricsKey, 'total_turns', '0');
        $this->redis->expire($metricsKey, 3600); // 1 hour TTL

        // Create initial TickLog entry
        TickLog::create([
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

        echo "Dispatched " . count($chunks) . " jobs for {$totalSectors} sectors.\n";
    }

    /**
     * Processor: Processes a specific chunk of dominions.
     */
    public function processTickJob(array $dominionIds, string $tickTime, string $tickId): void
    {
        $startTime = microtime(true);
        $metricsKey = self::METRICS_KEY_PREFIX . $tickId;

        $baseCitizens = (int)$this->configService->get('baseline_citizens_per_tick', 50);
        $baseCredits = (int)$this->configService->get('baseline_credits_per_tick', 100);
        $baseTurns = GameService::BASE_TURNS_PER_TICK;

        $hasProductionColumn = Capsule::schema()->hasColumn('units', 'production_credits');

        $query = Dominion::whereIn('id', $dominionIds)
            ->select(['dominions.*'])
            ->selectSub(function ($query) {
                $query->selectRaw('SUM(sl.buff_economy)')
                    ->from('dominion_structures as ds')
                    ->join('structure_levels as sl', function ($join) {
                        $join->on('ds.structure_id', '=', 'sl.structure_id')
                             ->on('ds.level', '=', 'sl.level');
                    })
                    ->whereColumn('ds.dominion_id', 'dominions.id');
            }, 'total_economy_buff')
            ->selectSub(function ($query) {
                $query->selectRaw('SUM(sl.buff_citizens_per_tick)')
                    ->from('dominion_structures as ds')
                    ->join('structure_levels as sl', function ($join) {
                        $join->on('ds.structure_id', '=', 'sl.structure_id')
                             ->on('ds.level', '=', 'sl.level');
                    })
                    ->whereColumn('ds.dominion_id', 'dominions.id');
            }, 'total_citizen_buff');

        if ($hasProductionColumn) {
            $query->selectSub(function ($query) {
                $query->selectRaw('SUM(dm.total_quantity * u.production_credits)')
                    ->from('dominion_manpower as dm')
                    ->join('units as u', 'dm.unit_id', '=', 'u.id')
                    ->whereColumn('dm.dominion_id', 'dominions.id');
            }, 'total_unit_production');
        }

        $localMetrics = [
            'credits' => 0,
            'citizens' => 0,
            'turns' => 0
        ];

        Capsule::transaction(function () use ($query, $baseCitizens, $baseCredits, $baseTurns, $tickTime, &$localMetrics) {
            $dominions = $query->get();
            foreach ($dominions as $dom) {
                $multiplier = 1 + ((float)($dom->total_economy_buff ?? 0) / 100);
                $basePlusUnit = $baseCredits + (int)($dom->total_unit_production ?? 0);
                $creditsGained = max(0, (int)floor($basePlusUnit * $multiplier));
                
                $citizenGained = max(0, $baseCitizens + (int)($dom->total_citizen_buff ?? 0));

                Capsule::table('dominions')
                    ->where('id', $dom->id)
                    ->update([
                        'credits'   => Capsule::raw('credits + ' . (int)$creditsGained),
                        'citizens'  => Capsule::raw('citizens + ' . (int)$citizenGained),
                        'turns'     => Capsule::raw('turns + ' . (int)$baseTurns),
                        'last_tick' => $tickTime
                    ]);

                $localMetrics['credits'] += $creditsGained;
                $localMetrics['citizens'] += $citizenGained;
                $localMetrics['turns'] += $baseTurns;
            }
        });

        // Atomically update Redis metrics
        $this->redis->hincrby($metricsKey, 'total_credits', $localMetrics['credits']);
        $this->redis->hincrby($metricsKey, 'total_citizens', $localMetrics['citizens']);
        $this->redis->hincrby($metricsKey, 'total_turns', $localMetrics['turns']);

        $durationMs = (microtime(true) - $startTime) * 1000;
        echo "[Chunk processed in " . round($durationMs, 2) . "ms]\n";
    }

    /**
     * Aggregator: Pulls metrics from Redis and finalizes the TickLog record.
     */
    public function finalizeTickLog(string $tickId, float $executionTimeMs): void
    {
        $metricsKey = self::METRICS_KEY_PREFIX . $tickId;
        $data = $this->redis->hgetall($metricsKey);

        if (empty($data)) {
            echo "Warning: No metrics found for Tick ID {$tickId}.\n";
            return;
        }

        $log = TickLog::where('metadata->tick_id', $tickId)->first();
        if ($log) {
            $log->total_credits_granted = (int)($data['total_credits'] ?? 0);
            $log->total_citizens_born = (int)($data['total_citizens'] ?? 0);
            $log->total_turns_granted = (int)($data['total_turns'] ?? 0);
            $log->execution_time_ms = $executionTimeMs;
            
            $meta = $log->metadata;
            $meta['status'] = 'completed';
            $log->metadata = $meta;
            
            $log->save();
        }

        $this->redis->del($metricsKey);
        echo "Tick ID {$tickId} finalized.\n";
    }

    /**
     * Legacy support / wrapper for unit tests.
     */
    public function processGlobalTick(): void
    {
        $this->dispatchTickJobs();
    }
}
