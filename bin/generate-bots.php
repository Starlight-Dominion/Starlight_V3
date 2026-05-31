#!/usr/bin/env php
<?php

// bin/generate-bots.php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use sdo\Infrastructure\Eloquent;
use sdo\Models\User;
use sdo\Models\Race;
use sdo\Models\Structure;
use sdo\Models\DominionStructure;
use sdo\Models\Unit;
use sdo\Models\DominionManpower;
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

function generateDominionName($botName) {
    $suffixes = ['Kingdom', 'Empire', 'Realm', 'Domain', 'Lands', 'Territory', 'Nation', 'State', 'Republic', 'Union'];
    return $botName . "'s " . $suffixes[array_rand($suffixes)];
}

$created = 0;
$attempts = 0;
$maxAttempts = 200;

$races = Race::all();
$structures = Structure::all();
$units = Unit::all();
$profiles = \sdo\Models\BotProfile::all();

while ($created < 100 && $attempts < $maxAttempts) {
    $attempts++;

    $botName = generateBotName($prefixes, $suffixes, $middle);

    // Ensure unique username
    $existing = User::where('username', $botName)->exists();
    if ($existing) {
        continue;
    }

    $dominionName = generateDominionName($botName);
    $email = 'bot_' . strtolower(str_replace("'", "", str_replace(" ", "_", $botName))) . '@starlight.ai';

    try {
        Capsule::transaction(function () use ($botName, $email, $dominionName, $races, $structures, $units, $profiles) {
            $user = User::create([
                'username' => $botName,
                'email' => $email,
                'password' => bin2hex(random_bytes(16)),
                'is_bot' => true,
                'bot_profile_id' => $profiles->isEmpty() ? null : $profiles->random()->id
            ]);

            $dominion = $user->dominion()->create([
                'name'    => $dominionName,
                'race_id' => $races->random()->id,
                'credits' => rand(5000, 50000),
                'citizens' => rand(300, 1000),
                'turns'    => rand(50, 150),
                'foundation_hp' => 1000,
                'foundation_max_hp' => 1000,
                'current_mine_tier' => rand(1, 3),
                'current_mine_level' => rand(1, 5),
                'housing_level' => rand(1, 4),
            ]);

            foreach ($structures as $s) {
                DominionStructure::create([
                    'dominion_id' => $dominion->id,
                    'structure_id' => $s->id,
                    'level' => rand(0, 5)
                ]);
            }

            foreach ($units as $u) {
                DominionManpower::create([
                    'dominion_id' => $dominion->id,
                    'unit_id' => $u->id,
                    'total_quantity' => rand(0, 50)
                ]);
            }
        });

        $created++;
        echo "Created bot {$created}/100: {$botName}\n";
    } catch (\Exception $e) {
        echo "Failed to create {$botName}: " . $e->getMessage() . "\n";
    }
}

echo "\nDone! Created {$created} bots.\n";
