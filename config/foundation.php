<?php

// config/foundation.php

return [
    'tiers' => [
        1 => [
            'name' => 'Thatch & Mud', 
            'hp' => 1000, 
            'cost' => 50000, 
            'player_level_req' => 1,
            'description' => 'A primitive start for a rising kingdom.'
        ],
        2 => [
            'name' => 'Timber Frame', 
            'hp' => 5000, 
            'cost' => 250000, 
            'player_level_req' => 5,
            'description' => 'Hardened wood provides a sturdy base.'
        ],
        3 => [
            'name' => 'Cobblestone', 
            'hp' => 15000, 
            'cost' => 1000000, 
            'player_level_req' => 10,
            'description' => 'Stone walls repel minor incursions.'
        ],
        4 => [
            'name' => 'Reinforced Masonry', 
            'hp' => 40000, 
            'cost' => 5000000, 
            'player_level_req' => 15,
            'description' => 'Expertly carved stone for a lasting legacy.'
        ],
        5 => [
            'name' => 'Iron-Clad Granite', 
            'hp' => 100000, 
            'cost' => 15000000, 
            'player_level_req' => 20,
            'description' => 'Metal plates bolted to solid rock.'
        ],
        6 => [
            'name' => 'Dwarven Deepstone', 
            'hp' => 250000, 
            'cost' => 50000000, 
            'player_level_req' => 25,
            'description' => 'Mystical stone mined from the roots of the world.'
        ],
        7 => [
            'name' => 'Obsidian Bulwark', 
            'hp' => 600000, 
            'cost' => 150000000, 
            'player_level_req' => 30,
            'description' => 'Volcanic glass forged in arcane heat.'
        ],
        8 => [
            'name' => 'Mithril Reinforced', 
            'hp' => 1500000, 
            'cost' => 500000000, 
            'player_level_req' => 40,
            'description' => 'Light as a feather, hard as dragon scale.'
        ],
        9 => [
            'name' => 'Celestial Marble', 
            'hp' => 5000000, 
            'cost' => 1500000000, 
            'player_level_req' => 50,
            'description' => 'Stone infused with the essence of the stars.'
        ],
        10 => [
            'name' => 'Adamantine Citadel', 
            'hp' => 15000000, 
            'cost' => 5000000000, 
            'player_level_req' => 60,
            'description' => 'The ultimate expression of sovereign power.'
        ],
    ],
    'upgrades' => [
        'moat' => [
            'name' => 'Moat',
            'description' => "Grants a +25% bonus to your foundation's HP.",
            'cost_multiplier' => 0.5, 
            'bonus_type' => 'hp_percentage',
            'bonus_value' => 0.25,
        ],
        'hot_oil_vat' => [
            'name' => 'Hot Oil Vat',
            'description' => "Grants a +5% bonus to your kingdom's total defense.",
            'cost_multiplier' => 0.5,
            'bonus_type' => 'defense_percentage',
            'bonus_value' => 0.05,
        ],
    ],
];
