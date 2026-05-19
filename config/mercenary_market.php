<?php

// config/mercenary_market.php

return [
    'levels' => [
        1 => ['cost' => 5000, 'guards' => 4, 'soldiers' => 2, 'spies' => 1, 'sentries' => 1],
        2 => ['cost' => 15000, 'guards' => 8, 'soldiers' => 4, 'spies' => 2, 'sentries' => 2],
        3 => ['cost' => 40000, 'guards' => 16, 'soldiers' => 8, 'spies' => 4, 'sentries' => 4],
        4 => ['cost' => 100000, 'guards' => 32, 'soldiers' => 16, 'spies' => 8, 'sentries' => 8],
        5 => ['cost' => 250000, 'guards' => 64, 'soldiers' => 32, 'spies' => 16, 'sentries' => 16],
    ],
    'max_level' => 5,
];
