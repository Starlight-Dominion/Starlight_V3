<?php
declare(strict_types=1);
use Phinx\Seed\AbstractSeed;

class BotProfileSeeder extends AbstractSeed {
    public function run(): void {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute('TRUNCATE TABLE bot_profiles;');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

        $data = [
            [
                'name' => 'Warmonger',
                'description' => 'Highly aggressive. Prioritizes military training and consistent attacks.',
                'action_frequency_minutes' => 30,
                'weight_attack' => 60,
                'weight_train' => 25,
                'weight_build' => 10,
                'weight_explore' => 5,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Empire Builder',
                'description' => 'Focuses on structural growth and long-term economic stability.',
                'action_frequency_minutes' => 60,
                'weight_attack' => 5,
                'weight_train' => 15,
                'weight_build' => 60,
                'weight_explore' => 20,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Rogue Agent',
                'description' => 'Balanced behavior with a slight tilt towards exploration and espionage.',
                'action_frequency_minutes' => 45,
                'weight_attack' => 20,
                'weight_train' => 20,
                'weight_build' => 20,
                'weight_explore' => 40,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Passive Drone',
                'description' => 'Low activity bot that primarily exists to populate the sector list.',
                'action_frequency_minutes' => 120,
                'weight_attack' => 0,
                'weight_train' => 5,
                'weight_build' => 5,
                'weight_explore' => 90,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->table('bot_profiles')->insert($data)->saveData();
    }
}
