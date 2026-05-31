#!/usr/bin/env php
<?php

// bin/bot-processor.php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use sdo\Infrastructure\Eloquent;
use sdo\Services\BotAutomationService;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Boot Eloquent ORM
Eloquent::boot();

$container = require __DIR__ . '/../config/container.php';
$botService = $container->get(BotAutomationService::class);

echo "Starting Bot Processor...\n";

while (true) {
    try {
        echo "[" . date('Y-m-d H:i:s') . "] Processing bots...\n";
        $botService->processBots();
    } catch (\Throwable $e) {
        fwrite(STDERR, "bot-processor error: {$e->getMessage()}\n");
    }
    
    // Sleep for 60 seconds
    sleep(60);
}
