#!/usr/bin/env php
<?php

// bin/tick-dispatcher.php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use sdo\Infrastructure\Eloquent;
use sdo\Services\TickService;
use sdo\Services\GameService;
use sdo\Models\Dominion;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Boot Eloquent ORM
Eloquent::boot();

// We need the DI container
$container = require __DIR__ . '/../config/container.php';

$tickService = $container->get(TickService::class);
$gameService = $container->get(GameService::class);

echo "Starting Distributed Tick Dispatcher...\n";
echo "Server Time: " . (new DateTime('now', new DateTimeZone(GameService::TIMEZONE)))->format('Y-m-d H:i:s T') . "\n";

while (true) {
    $secondsToWait = $gameService->getSecondsToNextTick();
    
    if ($secondsToWait <= 0) {
        $secondsToWait = 1;
    }

    echo "Next absolute tick boundary in {$secondsToWait} seconds. Sleeping...\n";
    sleep($secondsToWait);

    try {
        echo "Boundary reached. Dispatching tick jobs...\n";
        $startTime = microtime(true);
        
        // 1. Dispatch Jobs
        $tickService->dispatchTickJobs();
        
        // 2. Wait for completion (simple heuristic for now)
        // In a more complex system, we'd use a completion stream.
        // Given 15m intervals, 10s wait is safe.
        sleep(10);
        
        // 3. Finalize Log
        // Note: The dispatchTickJobs method doesn't return the tickId easily without refactor.
        // Let's grab the most recent dispatched log.
        $latestLog = \sdo\Models\TickLog::orderBy('id', 'desc')->first();
        if ($latestLog && ($latestLog->metadata['status'] ?? '') === 'dispatched') {
            $executionTimeMs = (microtime(true) - $startTime) * 1000;
            $tickService->finalizeTickLog($latestLog->metadata['tick_id'], $executionTimeMs);
        }

    } catch (\Exception $e) {
        echo "CRITICAL: Dispatcher Failed! " . $e->getMessage() . "\n";
    }

    sleep(5);
}
