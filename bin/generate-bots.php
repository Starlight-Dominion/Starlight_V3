#!/usr/bin/env php
<?php

// bin/generate-bots.php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Shadowreign\Infrastructure\Eloquent;
use Shadowreign\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Boot Eloquent ORM
Eloquent::boot();

echo "Generating 100 bot players...\n";

// Gamer name components
$prefixes = [
    'Dark', 'Shadow', 'Night', 'Cyber', 'Neon', 'Pixel', 'Storm', 'Fire', 'Ice', 'Thunder',
    'Ghost', 'Phantom', 'Steel', 'Iron', 'Golden', 'Silver', 'Crystal', 'Magic', 'Epic', 'Mega',
    'Ultra', 'Super', 'Hyper', 'Turbo', 'Power', 'Death', 'Life', 'Blood', 'Soul', 'Spirit',
    'Dragon', 'Wolf', 'Tiger', 'Eagle', 'Viper', 'Cobra', 'Hawk', 'Bear', 'Lion', 'Shark',
    'Ninja', 'Samurai', 'Warrior', 'Knight', 'Mage', 'Wizard', 'Rogue', 'Hunter', 'Slayer', 'Reaper'
];

$suffixes = [
    'Slayer', 'Warrior', 'Knight', 'Mage', 'Hunter', 'Striker', 'Blade', 'Fang', 'Claw', 'Fist',
    'Master', 'Lord', 'King', 'Queen', 'Prince', 'Princess', 'Hero', 'Legend', 'Ghost', 'Phantom',
    'Storm', 'Fire', 'Ice', 'Thunder', 'Lightning', 'Shadow', 'Blaze', 'Frost', 'Flame', 'Spark',
    'Rider', 'Walker', 'Runner', 'Crawler', 'Flyer', 'Diver', 'Breaker', 'Crusher', 'Smasher', 'Bane',
    'X', 'Z', 'XD', 'Pro', 'Gamer', 'Player', 'Elite', 'Prime', 'Alpha', 'Omega'
];

$middle = [
    'The', 'Of', 'From', 'In', 'On', 'At', 'With', 'And', 'Or', 'But',
    '', '', '', '', '', '', '', '', '', '',
    '', '', '', '', '', '', '', '', '', '',
];

function generateBotName($prefixes, $suffixes, $middle) {
    $prefix = $prefixes[array_rand($prefixes)];
    $suffix = $suffixes[array_rand($suffixes)];
    $mid = $middle[array_rand($middle)];

    if ($mid) {
        return $prefix . $mid . $suffix;
    }
    return $prefix . $suffix;
}

function generateKingdomName($botName) {
    $suffixes = ['Kingdom', 'Empire', 'Realm', 'Domain', 'Lands', 'Territory', 'Nation', 'State', 'Republic', 'Union'];
    return $botName . "'s " . $suffixes[array_rand($suffixes)];
}

$created = 0;
$attempts = 0;
$maxAttempts = 200;

while ($created < 100 && $attempts < $maxAttempts) {
    $attempts++;

    $botName = generateBotName($prefixes, $suffixes, $middle);

    // Ensure unique username
    $existing = User::where('username', $botName)->first();
    if ($existing) {
        continue;
    }

    $kingdomName = generateKingdomName($botName);
    $email = 'bot_' . strtolower(str_replace("'", "", str_replace(" ", "_", $botName))) . '@shadowreign.ai';

    try {
        Capsule::transaction(function () use ($botName, $email, $kingdomName) {
            $user = User::create([
                'username' => $botName,
                'email' => $email,
                'password' => bin2hex(random_bytes(16)),
                'is_bot' => true,
            ]);

            $user->kingdom()->create([
                'kingdom_name' => $kingdomName,
                'gold' => rand(5000, 50000),
                'citizens' => rand(300, 1000),
                'turns' => rand(50, 150),
                'unit_guards' => rand(0, 30),
                'unit_soldiers' => rand(0, 20),
                'unit_spies' => rand(0, 10),
                'unit_sentries' => rand(0, 15),
                'miners' => rand(0, 25),
                'base_gold_per_tick' => rand(80, 200),
                'current_mine_tier' => rand(1, 3),
                'current_mine_level' => rand(1, 5),
                'foundation_level' => rand(1, 5),
                'housing_level' => rand(1, 4),
            ]);
        });

        $created++;
        echo "Created bot {$created}/100: {$botName}\n";
    } catch (\Exception $e) {
        echo "Failed to create {$botName}: " . $e->getMessage() . "\n";
    }
}

echo "\nDone! Created {$created} bots.\n";
