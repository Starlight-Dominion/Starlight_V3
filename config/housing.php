<?php

// config/housing.php

return [
    'levels' => [
        1 => ['cost' => 0, 'citizens_per_tick' => 50],
        2 => ['cost' => 1000, 'citizens_per_tick' => 60],
        3 => ['cost' => 5000, 'citizens_per_tick' => 75],
        4 => ['cost' => 20000, 'citizens_per_tick' => 100],
        5 => ['cost' => 75000, 'citizens_per_tick' => 150],
    ],
    'max_level' => 5,
];
