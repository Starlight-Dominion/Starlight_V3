<?php

// config/mines.php

// Helper function to generate level progression
if (!function_exists('generateMineLevels')) {
function generateMineLevels($initialCost, $costMultiplier, $initialProduction, $productionMultiplier) {
    $levels = [];
    $cost = $initialCost;
    $production = $initialProduction;
    for ($i = 1; $i <= 150; $i++) {
        $levels[$i] = [
            'cost' => floor($cost),
            'production_per_miner' => round($production, 2),
        ];
        $cost *= $costMultiplier;
        $production *= $productionMultiplier;
    }
    return $levels;
    }
}

return [
    'unlocks' => [
        1 => 1,   // Mine 1 unlocks at player level 1
        2 => 5,   // Mine 2 unlocks at player level 5
        3 => 12,
        4 => 20,
        5 => 30,
        6 => 45,
        7 => 60,
        8 => 80,
        9 => 100,
        10 => 125,
    ],
    'mines' => [
        1 => generateMineLevels(1000, 1.15, 5, 1.05),
        2 => generateMineLevels(5000, 1.16, 8, 1.06),
        3 => generateMineLevels(25000, 1.17, 12, 1.07),
        // ... and so on for all 10 mines with varying initial values
        4 => generateMineLevels(100000, 1.18, 18, 1.08),
        5 => generateMineLevels(500000, 1.19, 25, 1.09),
        6 => generateMineLevels(2000000, 1.2, 35, 1.1),
        7 => generateMineLevels(10000000, 1.21, 50, 1.11),
        8 => generateMineLevels(50000000, 1.22, 70, 1.12),
        9 => generateMineLevels(250000000, 1.23, 100, 1.13),
        10 => generateMineLevels(1000000000, 1.24, 150, 1.14),
    ],
    'base_gold_per_tick' => 100,
];
