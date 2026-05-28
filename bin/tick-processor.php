#!/usr/bin/env php
<?php

// bin/tick-processor.php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Predis\Client;
use sdo\Infrastructure\Eloquent;
use sdo\Services\TickService;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

Eloquent::boot();
$container = require __DIR__ . '/../config/container.php';

$redis = $container->get(Client::class);
$tickService = $container->get(TickService::class);

$stream = TickService::STREAM_KEY;
$group = 'sdo-tick-processors';
$consumer = gethostname() . '-' . getmypid();

echo "Starting Tick Processor [{$consumer}]...\n";

// 1. Create Consumer Group if it doesn't exist
try {
    $redis->executeRaw(['XGROUP', 'CREATE', $stream, $group, '0', 'MKSTREAM']);
} catch (Exception $e) {
    // Group already exists, ignore
}

while (true) {
    try {
        // Read from stream
        // > = read new messages only
        $response = $redis->executeRaw(['XREADGROUP', 'GROUP', $group, $consumer, 'COUNT', '1', 'BLOCK', '2000', 'STREAMS', $stream, '>']);

        if (!$response || !isset($response[0][1])) {
            continue;
        }

        foreach ($response[0][1] as $message) {
            $messageId = $message[0];
            $fields = normalizeStreamFields($message[1]);
            
            $raw = $fields['data'] ?? null;
            if ($raw) {
                $data = json_decode($raw, true);
                if (is_array($data)) {
                    echo "Processing chunk for Tick {$data['tick_id']} ({$data['tick_time']})...\n";
                    $tickService->processTickJob($data['dominion_ids'], $data['tick_time'], $data['tick_id']);
                }
            }

            // Acknowledge message
            $redis->executeRaw(['XACK', $stream, $group, $messageId]);
            // Delete message (we don't need history in the stream)
            $redis->executeRaw(['XDEL', $stream, $messageId]);
        }

    } catch (Throwable $e) {
        fwrite(STDERR, "tick-processor error: {$e->getMessage()}\n");
        usleep(1000000);
    }
}

/**
 * Normalizes Redis Stream fields.
 */
function normalizeStreamFields(array $rawFields): array
{
    $normalized = [];
    $count = count($rawFields);
    for ($i = 0; $i + 1 < $count; $i += 2) {
        $normalized[$rawFields[$i]] = $rawFields[$i + 1];
    }
    return $normalized;
}
