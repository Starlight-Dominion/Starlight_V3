#!/usr/bin/env php
<?php

// bin/tick-worker.php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use sdo\Infrastructure\Eloquent;
use sdo\Services\TickService;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Boot Eloquent ORM
Eloquent::boot();

// We need the DI container to get the PDO instance
$container = require __DIR__ . '/../config/container.php';

// Instantiate and run the service
$tickService = $container->get(TickService::class);
$gameService = $container->get(sdo\Services\GameService::class);

echo "Starting Global Tick Daemon...\n";
echo "Server Time: " . (new DateTime('now', new DateTimeZone(sdo\Services\GameService::TIMEZONE)))->format('Y-m-d H:i:s T') . "\n";

while (true) {
    $secondsToWait = $gameService->getSecondsToNextTick();
    
    // Safety buffer: If we are within 1 second of the tick, wait for it to pass
    if ($secondsToWait <= 0) {
        $secondsToWait = 1;
    }

    echo "Next absolute tick boundary in {$secondsToWait} seconds. Sleeping...\n";
    sleep($secondsToWait);

    try {
        echo "Boundary reached. Executing Global Tick Transaction...\n";
        $tickService->processGlobalTick();
    } catch (\Exception $e) {
        echo "CRITICAL: Global Tick Failed! " . $e->getMessage() . "\n";
    }

    // Small cooldown after processing to prevent immediate re-triggering if the boundary math is tight
    sleep(2);
}
