#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use sdo\Repositories\Eloquent\EloquentTickRepository;
use sdo\Services\TickService;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();
ob_implicit_flush(true);

const BASE_CREDITS = 100;
const BASE_CITIZENS = 50;
const BASE_TURNS = 4;
const TICK_TIME = '2026-06-08 12:00:00';

/** @var array<int, int> */
const ECONOMY_BUFFS = [1 => -30, 2 => -10, 3 => 0, 4 => 15, 5 => 40];
/** @var array<int, int> */
const CITIZEN_BUFFS = [1 => -20, 2 => 0, 3 => 10, 4 => 25, 5 => 60];

$count = isset($argv[1]) ? max(1, (int)$argv[1]) : 100000;
$seedChunk = isset($argv[2]) ? max(100, (int)$argv[2]) : 1000;
$benchDbName = $argv[3] ?? (getenv('BENCH_DB_NAME') ?: 'sdo_bench');
$setRuns = isset($argv[4]) ? max(1, (int)$argv[4]) : 5;
$legacyRuns = isset($argv[5]) ? max(1, (int)$argv[5]) : 1;

bootBenchmarkConnection($benchDbName);

Capsule::connection()->disableQueryLog();

echo sprintf("Preparing benchmark schema in MariaDB database '%s'...\n", $benchDbName);
prepareSchema();
seedReferenceData();
seedBenchmarkData($count, $seedChunk);

$repository = new EloquentTickRepository();
$dominionIds = range(1, $count);

$setBasedRuns = [];
for ($i = 1; $i <= $setRuns; $i++) {
    echo sprintf("\n--- Set-based run %d/%d ---\n", $i, $setRuns);
    $setBasedRuns[] = runSetBasedBenchmark($repository, $dominionIds);
}

$legacyRunsData = [];
for ($i = 1; $i <= $legacyRuns; $i++) {
    echo sprintf("\n--- Legacy run %d/%d ---\n", $i, $legacyRuns);
    $legacyRunsData[] = runLegacyBenchmark($repository, $dominionIds);
}

$setBasedTimes = array_map(static fn(array $r): float => (float)$r['elapsed_ms'], $setBasedRuns);
$legacyTimes = array_map(static fn(array $r): float => (float)$r['elapsed_ms'], $legacyRunsData);

echo "\nReal DB benchmark summary (MariaDB):\n";
echo sprintf("- Set-based runs: %d | p50=%.2f ms | p95=%.2f ms\n", $setRuns, percentile($setBasedTimes, 0.50), percentile($setBasedTimes, 0.95));
echo sprintf("- Legacy runs:    %d | p50=%.2f ms | p95=%.2f ms\n", $legacyRuns, percentile($legacyTimes, 0.50), percentile($legacyTimes, 0.95));
echo sprintf("- Speedup (p50): %.2fx\n", percentile($legacyTimes, 0.50) > 0 ? (percentile($legacyTimes, 0.50) / percentile($setBasedTimes, 0.50)) : 0.0);

echo "\nBenchmark complete.\n";

function percentile(array $values, float $q): float
{
    if (empty($values)) {
        return 0.0;
    }

    sort($values, SORT_NUMERIC);
    $n = count($values);
    if ($n === 1) {
        return (float)$values[0];
    }

    $pos = ($n - 1) * $q;
    $lower = (int)floor($pos);
    $upper = (int)ceil($pos);
    if ($lower === $upper) {
        return (float)$values[$lower];
    }

    $weight = $pos - $lower;
    return (1.0 - $weight) * (float)$values[$lower] + $weight * (float)$values[$upper];
}

function bootBenchmarkConnection(string $database): void
{
    $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1');
    $port = (int)(getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? 3306));
    $user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
    $pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');

    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $safeDatabase = str_replace('`', '', $database);
    try {
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $safeDatabase . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    } catch (PDOException $e) {
        $existsStmt = $pdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
        $existsStmt->execute([$safeDatabase]);
        if (!$existsStmt->fetchColumn()) {
            throw $e;
        }

        echo "Note: CREATE DATABASE denied for current DB user; using existing schema.\n";
    }

    $capsule = new Capsule();
    $capsule->addConnection([
        'driver' => 'mysql',
        'host' => $host,
        'database' => $database,
        'username' => $user,
        'password' => $pass,
        'port' => $port,
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'strict' => false,
    ]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
}

function runSetBasedBenchmark(EloquentTickRepository $repository, array $dominionIds): array
{
    resetDominionsFromSeed();

    $startedAt = microtime(true);
    $metrics = $repository->applyTickResultsSetBased($dominionIds, BASE_CREDITS, BASE_CITIZENS, BASE_TURNS, TICK_TIME);
    $elapsedMs = (microtime(true) - $startedAt) * 1000;

    assertMetricsMatchCurrentDominions((int)count($dominionIds), $metrics);
    snapshotSqlResults();

    echo sprintf("Set-based SQL benchmark: updated %d dominions in %.2f ms\n", count($dominionIds), $elapsedMs);

    return [
        'elapsed_ms' => $elapsedMs,
        'metrics' => $metrics,
    ];
}

function runLegacyBenchmark(EloquentTickRepository $repository, array $dominionIds): array
{
    resetDominionsFromSeed();

    $metrics = [
        'credits' => 0,
        'citizens' => 0,
        'turns' => 0,
    ];

    $startedAt = microtime(true);
    $chunks = array_chunk($dominionIds, TickService::BATCH_SIZE);
    $totalChunks = count($chunks);
    foreach ($chunks as $i => $chunkIds) {
        $dominions = $repository->getTickData($chunkIds);
        foreach ($dominions as $dominion) {
            $multiplier = 1 + ((float)($dominion->total_economy_buff ?? 0) / 100);
            $basePlusUnit = BASE_CREDITS + (int)($dominion->total_unit_production ?? 0);
            $creditsGained = max(0, (int)floor($basePlusUnit * $multiplier));
            $citizensGained = max(0, BASE_CITIZENS + (int)($dominion->total_citizen_buff ?? 0));

            $repository->applyTickResults(
                (int)$dominion->id,
                $creditsGained,
                $citizensGained,
                BASE_TURNS,
                TICK_TIME
            );

            $metrics['credits'] += $creditsGained;
            $metrics['citizens'] += $citizensGained;
            $metrics['turns'] += BASE_TURNS;
        }

        if ((($i + 1) % 200) === 0 || ($i + 1) === $totalChunks) {
            echo sprintf("Legacy progress: %d/%d chunks processed\n", $i + 1, $totalChunks);
        }
    }
    $elapsedMs = (microtime(true) - $startedAt) * 1000;

    assertMetricsMatchCurrentDominions((int)count($dominionIds), $metrics);
    echo sprintf("Legacy tick benchmark (batch size %d): updated %d dominions in %.2f ms\n", TickService::BATCH_SIZE, count($dominionIds), $elapsedMs);

    try {
        assertLegacyMatchesSqlSnapshot();
    } catch (RuntimeException $e) {
        echo "WARNING: SQL and legacy final row states are not identical.\n";
        echo $e->getMessage() . "\n";
    }

    return [
        'elapsed_ms' => $elapsedMs,
        'metrics' => $metrics,
    ];
}

function prepareSchema(): void
{
    echo "- Dropping old benchmark tables...\n";
    Capsule::statement('SET FOREIGN_KEY_CHECKS=0');

    Capsule::statement('DROP TABLE IF EXISTS dominion_manpower');
    Capsule::statement('DROP TABLE IF EXISTS dominion_structures');
    Capsule::statement('DROP TABLE IF EXISTS structure_levels');
    Capsule::statement('DROP TABLE IF EXISTS units');
    Capsule::statement('DROP TABLE IF EXISTS structures');
    Capsule::statement('DROP TABLE IF EXISTS dominions');
    Capsule::statement('DROP TABLE IF EXISTS dominions_seed');
    Capsule::statement('DROP TABLE IF EXISTS dominions_sql_snapshot');
    Capsule::statement('DROP TABLE IF EXISTS users');

    echo "- Creating benchmark tables...\n";
    Capsule::statement('CREATE TABLE users (
        id INT UNSIGNED NOT NULL PRIMARY KEY,
        username VARCHAR(64) NOT NULL UNIQUE,
        email VARCHAR(128) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    Capsule::statement('CREATE TABLE dominions (
        id INT UNSIGNED NOT NULL PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        name VARCHAR(128) NOT NULL UNIQUE,
        credits BIGINT NOT NULL DEFAULT 0,
        citizens INT NOT NULL DEFAULT 0,
        turns INT NOT NULL DEFAULT 0,
        last_tick DATETIME NULL,
        KEY idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    Capsule::statement('CREATE TABLE dominions_seed (
        id INT UNSIGNED NOT NULL PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        name VARCHAR(128) NOT NULL UNIQUE,
        credits BIGINT NOT NULL DEFAULT 0,
        citizens INT NOT NULL DEFAULT 0,
        turns INT NOT NULL DEFAULT 0,
        last_tick DATETIME NULL,
        KEY idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    Capsule::statement('CREATE TABLE dominions_sql_snapshot (
        id INT UNSIGNED NOT NULL PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        name VARCHAR(128) NOT NULL UNIQUE,
        credits BIGINT NOT NULL DEFAULT 0,
        citizens INT NOT NULL DEFAULT 0,
        turns INT NOT NULL DEFAULT 0,
        last_tick DATETIME NULL,
        KEY idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    Capsule::statement('CREATE TABLE structures (
        id INT UNSIGNED NOT NULL PRIMARY KEY,
        slug VARCHAR(64) NOT NULL UNIQUE,
        name VARCHAR(128) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    Capsule::statement('CREATE TABLE structure_levels (
        structure_id INT UNSIGNED NOT NULL,
        level INT NOT NULL,
        buff_economy INT NOT NULL DEFAULT 0,
        buff_citizens_per_tick INT NOT NULL DEFAULT 0,
        PRIMARY KEY (structure_id, level)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    Capsule::statement('CREATE TABLE dominion_structures (
        dominion_id INT UNSIGNED NOT NULL,
        structure_id INT UNSIGNED NOT NULL,
        level INT NOT NULL DEFAULT 0,
        PRIMARY KEY (dominion_id, structure_id),
        KEY idx_structure (structure_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    Capsule::statement('CREATE TABLE units (
        id INT UNSIGNED NOT NULL PRIMARY KEY,
        slug VARCHAR(64) NOT NULL UNIQUE,
        production_credits INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    Capsule::statement('CREATE TABLE dominion_manpower (
        dominion_id INT UNSIGNED NOT NULL,
        unit_id INT UNSIGNED NOT NULL,
        total_quantity INT NOT NULL DEFAULT 0,
        PRIMARY KEY (dominion_id, unit_id),
        KEY idx_unit (unit_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    Capsule::statement('SET FOREIGN_KEY_CHECKS=1');
    echo "- Schema ready.\n";
}

function seedReferenceData(): void
{
    echo "- Seeding reference data...\n";
    Capsule::table('structures')->insert([
        ['id' => 1, 'slug' => 'economy', 'name' => 'Economy'],
        ['id' => 2, 'slug' => 'housing', 'name' => 'Housing'],
    ]);

    $levels = [];
    foreach (range(1, 5) as $level) {
        $levels[] = [
            'structure_id' => 1,
            'level' => $level,
            'buff_economy' => ECONOMY_BUFFS[$level],
            'buff_citizens_per_tick' => 0,
        ];
        $levels[] = [
            'structure_id' => 2,
            'level' => $level,
            'buff_economy' => 0,
            'buff_citizens_per_tick' => CITIZEN_BUFFS[$level],
        ];
    }
    Capsule::table('structure_levels')->insert($levels);

    Capsule::table('units')->insert([
        ['id' => 1, 'slug' => 'u1', 'production_credits' => 1],
        ['id' => 2, 'slug' => 'u2', 'production_credits' => 3],
        ['id' => 3, 'slug' => 'u3', 'production_credits' => 7],
    ]);
}

function seedBenchmarkData(int $count, int $batchSize): void
{
    echo sprintf("Seeding %d dominions in chunks of %d...\n", $count, $batchSize);

    $totalChunks = (int)ceil($count / $batchSize);

    $chunkNo = 0;
    for ($start = 1; $start <= $count; $start += $batchSize) {
        $chunkNo++;
        $end = min($count, $start + $batchSize - 1);

        $users = [];
        $dominions = [];
        $dominionStructures = [];
        $manpower = [];

        for ($id = $start; $id <= $end; $id++) {
            $users[] = [
                'id' => $id,
                'username' => 'bench_user_' . $id,
                'email' => 'bench_user_' . $id . '@example.test',
                'password' => 'x',
            ];

            $dominions[] = [
                'id' => $id,
                'user_id' => $id,
                'name' => 'Bench Dominion ' . $id,
                'credits' => initialCredits($id),
                'citizens' => initialCitizens($id),
                'turns' => initialTurns($id),
                'last_tick' => null,
            ];

            $economyLevel = economyLevel($id);
            if ($economyLevel > 0) {
                $dominionStructures[] = [
                    'dominion_id' => $id,
                    'structure_id' => 1,
                    'level' => $economyLevel,
                ];
            }

            $housingLevel = housingLevel($id);
            if ($housingLevel > 0) {
                $dominionStructures[] = [
                    'dominion_id' => $id,
                    'structure_id' => 2,
                    'level' => $housingLevel,
                ];
            }

            $manpower[] = ['dominion_id' => $id, 'unit_id' => 1, 'total_quantity' => $id % 21];
            $manpower[] = ['dominion_id' => $id, 'unit_id' => 2, 'total_quantity' => ($id * 2) % 17];
            $manpower[] = ['dominion_id' => $id, 'unit_id' => 3, 'total_quantity' => ($id * 3) % 13];
        }

        Capsule::table('users')->insert($users);
        Capsule::table('dominions_seed')->insert($dominions);
        if (!empty($dominionStructures)) {
            Capsule::table('dominion_structures')->insert($dominionStructures);
        }
        Capsule::table('dominion_manpower')->insert($manpower);

        if (($chunkNo % 20) === 0 || $chunkNo === $totalChunks) {
            echo sprintf("Seed progress: %d/%d chunks\n", $chunkNo, $totalChunks);
        }
    }
}

function resetDominionsFromSeed(): void
{
    echo "- Resetting working dominions from seed snapshot...\n";
    Capsule::statement('TRUNCATE TABLE dominions');
    Capsule::statement('INSERT INTO dominions (id, user_id, name, credits, citizens, turns, last_tick) SELECT id, user_id, name, credits, citizens, turns, last_tick FROM dominions_seed');
}

function assertMetricsMatchCurrentDominions(int $count, array $metrics): void
{
    $actualCount = (int)Capsule::table('dominions')->count();
    if ($actualCount !== $count) {
        throw new RuntimeException(sprintf('Expected %d rows, got %d rows', $count, $actualCount));
    }

    $aggregates = Capsule::table('dominions')
        ->selectRaw('COALESCE(SUM(credits), 0) as total_credits')
        ->selectRaw('COALESCE(SUM(citizens), 0) as total_citizens')
        ->selectRaw('COALESCE(SUM(turns), 0) as total_turns')
        ->first();

    $seedAggregates = Capsule::table('dominions_seed')
        ->selectRaw('COALESCE(SUM(credits), 0) as seed_credits')
        ->selectRaw('COALESCE(SUM(citizens), 0) as seed_citizens')
        ->selectRaw('COALESCE(SUM(turns), 0) as seed_turns')
        ->first();

    $expectedMetrics = [
        'credits' => (int)$aggregates->total_credits - (int)$seedAggregates->seed_credits,
        'citizens' => (int)$aggregates->total_citizens - (int)$seedAggregates->seed_citizens,
        'turns' => (int)$aggregates->total_turns - (int)$seedAggregates->seed_turns,
    ];

    $actualMetrics = [
        'credits' => (int)($metrics['credits'] ?? 0),
        'citizens' => (int)($metrics['citizens'] ?? 0),
        'turns' => (int)($metrics['turns'] ?? 0),
    ];

    if ($actualMetrics !== $expectedMetrics) {
        throw new RuntimeException(sprintf(
            'Metrics mismatch. expected=%s actual=%s',
            json_encode($expectedMetrics, JSON_UNESCAPED_SLASHES),
            json_encode($actualMetrics, JSON_UNESCAPED_SLASHES)
        ));
    }

    $missingTickCount = (int)Capsule::table('dominions')->where('last_tick', '!=', TICK_TIME)->count();
    if ($missingTickCount !== 0) {
        throw new RuntimeException(sprintf('Expected all rows to have last_tick=%s, but %d rows differ', TICK_TIME, $missingTickCount));
    }
}

function snapshotSqlResults(): void
{
    Capsule::statement('TRUNCATE TABLE dominions_sql_snapshot');
    Capsule::statement('INSERT INTO dominions_sql_snapshot (id, user_id, name, credits, citizens, turns, last_tick) SELECT id, user_id, name, credits, citizens, turns, last_tick FROM dominions');
}

function assertLegacyMatchesSqlSnapshot(): void
{
    $mismatchCount = (int)Capsule::table('dominions as l')
        ->join('dominions_sql_snapshot as s', 'l.id', '=', 's.id')
        ->where(function ($q) {
            $q->whereRaw('l.credits <> s.credits')
              ->orWhereRaw('l.citizens <> s.citizens')
              ->orWhereRaw('l.turns <> s.turns')
              ->orWhereRaw("COALESCE(l.last_tick, '1970-01-01 00:00:00') <> COALESCE(s.last_tick, '1970-01-01 00:00:00')");
        })
        ->count();

    if ($mismatchCount !== 0) {
        $examples = Capsule::table('dominions as l')
            ->join('dominions_sql_snapshot as s', 'l.id', '=', 's.id')
            ->where(function ($q) {
                $q->whereRaw('l.credits <> s.credits')
                  ->orWhereRaw('l.citizens <> s.citizens')
                  ->orWhereRaw('l.turns <> s.turns')
                  ->orWhereRaw("COALESCE(l.last_tick, '1970-01-01 00:00:00') <> COALESCE(s.last_tick, '1970-01-01 00:00:00')");
            })
            ->selectRaw('l.id as id, l.credits as legacy_credits, s.credits as sql_credits, l.citizens as legacy_citizens, s.citizens as sql_citizens, l.turns as legacy_turns, s.turns as sql_turns')
            ->orderBy('l.id')
            ->limit(10)
            ->get();

        throw new RuntimeException('Legacy results differ from set-based SQL snapshot. mismatches=' . $mismatchCount . ' examples=' . json_encode($examples, JSON_UNESCAPED_SLASHES));
    }
}

function economyLevel(int $id): int
{
    return $id % 6;
}

function housingLevel(int $id): int
{
    return ($id + 2) % 6;
}

function initialCredits(int $id): int
{
    return ($id * 7) % 1000;
}

function initialCitizens(int $id): int
{
    return ($id * 11) % 500;
}

function initialTurns(int $id): int
{
    return ($id * 13) % 200;
}
