#!/usr/bin/env php
<?php

// bin/tick-worker.php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Shadowreign\Infrastructure\Eloquent;
use Shadowreign\Services\TickService;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Boot Eloquent ORM
Eloquent::boot();

// We need the DI container to get the PDO instance
$container = require __DIR__ . '/../config/container.php';

// Instantiate and run the service
$tickService = $container->get(TickService::class);
$tickService->processGlobalTick();
