#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Predis\Client;
use sdo\Infrastructure\Eloquent;
use sdo\Services\DiscordLinkService;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

Eloquent::boot();
$container = require __DIR__ . '/../config/container.php';

/** @var Client $redis */
$redis = $container->get(Client::class);
/** @var DiscordLinkService $discordLinkService */
$discordLinkService = $container->get(DiscordLinkService::class);

actionWorker($redis, $discordLinkService);

function actionWorker(Client $redis, DiscordLinkService $discordLinkService): void
{
    $actionStream = $_ENV['SDO_ACTION_STREAM'] ?? 'sdo:discord:actions';
    $eventStream = $_ENV['SDO_EVENT_STREAM'] ?? 'sdo:discord:events';
    $checkpointPrefix = $_ENV['CHECKPOINT_KEY_PREFIX'] ?? 'sdo:discord:checkpoint';
    $consumer = 'sdo-action-worker';
    $checkpointKey = sprintf('%s:%s:%s', $checkpointPrefix, str_replace(':', '_', $consumer), str_replace(':', '_', $actionStream));

    $lastID = (string)($redis->get($checkpointKey) ?? '0-0');
    echo "Starting discord action worker. stream={$actionStream} resume_from={$lastID}\n";

    while (true) {
        try {
            $response = $redis->xread([$actionStream => $lastID], 10, 0);
            if (!$response || !isset($response[$actionStream])) {
                continue;
            }

            foreach ($response[$actionStream] as $entryID => $fields) {
                $lastID = $entryID;
                $raw = $fields['data'] ?? null;
                if (!is_string($raw)) {
                    $redis->set($checkpointKey, $lastID);
                    continue;
                }

                $decoded = json_decode($raw, true);
                if (!is_array($decoded)) {
                    $redis->set($checkpointKey, $lastID);
                    continue;
                }

                $resultEnvelope = $discordLinkService->processActionEnvelope($decoded);
                if (is_array($resultEnvelope)) {
                    $redis->xadd($eventStream, '*', ['data' => json_encode($resultEnvelope, JSON_UNESCAPED_SLASHES)]);
                }

                $redis->set($checkpointKey, $lastID);
            }
        } catch (Throwable $e) {
            fwrite(STDERR, "discord-action-worker error: {$e->getMessage()}\n");
            usleep(500000);
        }
    }
}
