<?php
declare(strict_types=1);
use Phinx\Seed\AbstractSeed;

class InitialDataSeeder extends AbstractSeed {
    public function run(): void {
        // Races
        $this->table('races')->insert([
            ['name' => 'Human', 'description' => 'Baseline adaptability.'],
            ['name' => 'Cyborg', 'description' => 'High-efficiency processing.'],
            ['name' => 'Shade', 'description' => 'Stealth-oriented biology.'],
            ['name' => 'Synthera', 'description' => 'Pure AI consciousness.']
        ])->saveData();

        // Units
        $this->table('units')->insert([
            ['slug' => 'guards', 'name' => 'Orbital Guards', 'description' => 'Defensive core.', 'cost_credits' => 50, 'cost_citizens' => 1, 'cost_turns' => 1, 'power_offense' => 5, 'power_defense' => 15],
            ['slug' => 'soldiers', 'name' => 'Soldiers', 'description' => 'Standard Infantry.', 'cost_credits' => 100, 'cost_citizens' => 1, 'cost_turns' => 2, 'power_offense' => 10, 'power_defense' => 10],
            ['slug' => 'spies', 'name' => 'Espionage Agents', 'description' => 'Intel gathered.', 'cost_credits' => 500, 'cost_citizens' => 1, 'cost_turns' => 5, 'power_offense' => 1, 'power_defense' => 1],
            ['slug' => 'sentries', 'name' => 'Detection Sentries', 'description' => 'Sentry link.', 'cost_credits' => 250, 'cost_citizens' => 1, 'cost_turns' => 3, 'power_offense' => 2, 'power_defense' => 25],
        ])->saveData();

        // Structures
        $this->table('structures')->insert([
            ['slug' => 'foundation', 'name' => 'Planetary Foundation', 'description' => 'Core integrity.'],
            ['slug' => 'tactical', 'name' => 'Tactical Uplink', 'description' => 'Offensive boost.'],
            ['slug' => 'aegis', 'name' => 'Aegis Projector', 'description' => 'Defensive boost.'],
            ['slug' => 'exchange', 'name' => 'Quantum Exchange', 'description' => 'Economic boost.'],
            ['slug' => 'grid', 'name' => 'Ecumenopolis Grid', 'description' => 'Population boost.'],
            ['slug' => 'armory', 'name' => 'Sector Armory', 'description' => 'Tech unlock.']
        ])->saveData();

        // Structure Levels (20 Tiers)
        $structs = $this->fetchAll('SELECT id FROM structures');
        foreach ($structs as $s) {
            for ($i = 1; $i <= 20; $i++) {
                $this->table('structure_levels')->insert([
                    'structure_id' => $s['id'],
                    'level' => $i,
                    'cost' => floor(50000 * pow(1.9, $i)),
                    'buff_hp' => $i * 10000,
                    'player_level_req' => $i,
                    'buff_name' => "Rank {$i}"
                ])->saveData();
            }
        }
    }
}