<?php

// config/units.php

return [
    'guards' => [
        'name' => 'Guards',
        'description' => 'Basic defensive units. Cheap and quick to train, but weak in open combat.',
        'cost_gold' => 50,
        'cost_citizens' => 1,
        'cost_turns' => 1,
        'power_offense' => 5,
        'power_defense' => 15,
    ],
    'soldiers' => [
        'name' => 'Soldiers',
        'description' => 'The backbone of any army. Balanced for both offense and defense.',
        'cost_gold' => 100,
        'cost_citizens' => 1,
        'cost_turns' => 2,
        'power_offense' => 10,
        'power_defense' => 10,
    ],
    'spies' => [
        'name' => 'Spies',
        'description' => 'Masters of espionage. Used for gathering intelligence and sabotage.',
        'cost_gold' => 500,
        'cost_citizens' => 1,
        'cost_turns' => 5,
        'power_offense' => 1,
        'power_defense' => 1,
    ],
    'sentries' => [
        'name' => 'Sentries',
        'description' => "Highly trained lookouts. Greatly increase your kingdom's defense against spies.",
        'cost_gold' => 250,
        'cost_citizens' => 1,
        'cost_turns' => 3,
        'power_offense' => 2,
        'power_defense' => 25,
    ],
];
