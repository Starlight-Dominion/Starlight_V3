<?php
declare(strict_types=1);
use Phinx\Seed\AbstractSeed;

class InitialDataSeeder extends AbstractSeed {
    public function run(): void {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute('TRUNCATE TABLE races;');
        $this->execute('TRUNCATE TABLE units;');
        $this->execute('TRUNCATE TABLE structures;');
        $this->execute('TRUNCATE TABLE structure_levels;');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

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
            ['slug' => 'workers', 'name' => 'Utility Workers', 'description' => 'Resource extraction and construction.', 'cost_credits' => 25, 'cost_citizens' => 1, 'cost_turns' => 1, 'power_offense' => 1, 'power_defense' => 2, 'production_credits' => 25],
        ])->saveData();

        // Structures
        $this->table('structures')->insert([
            ['slug' => 'foundation', 'name' => 'Planetary Foundation', 'description' => 'The Capital of your Dominion, the last line of defense.', 'max_level' => 20],
            ['slug' => 'economy', 'name' => 'Economic Hub', 'description' => 'The Economic center of your dominion.', 'max_level' => 20],
            ['slug' => 'armory', 'name' => 'Sector Armory', 'description' => 'Advanced military research and development facility for tactical equipment.', 'max_level' => 20]
        ])->saveData();

        // 1. Foundation Levels
        $foundation = $this->fetchRow("SELECT id FROM structures WHERE slug = 'foundation'");
        $fLevels = [];
        for ($i = 1; $i <= 20; $i++) {
            $fLevels[] = [
                'structure_id' => $foundation['id'],
                'level' => $i,
                'cost' => floor(50000 * pow(1.9, $i)),
                'buff_hp' => $i * 10000,
                'player_level_req' => $i,
                'buff_name' => "Rank {$i}"
            ];
        }
        $this->table('structure_levels')->insert($fLevels)->saveData();

        // 2. Economy Levels
        $economy = $this->fetchRow("SELECT id FROM structures WHERE slug = 'economy'");
        $eLevels = [
            1 => ['name' => 'Orbital Trade Post', 'cost' => 200, 'bonus' => 5],
            2 => ['name' => 'Asteroid Mining Colony', 'cost' => 650, 'bonus' => 5],
            3 => ['name' => 'Planetary Free Trade Zone', 'cost' => 2000, 'bonus' => 10],
            4 => ['name' => 'Interplanetary Merchant Fleet', 'cost' => 5000, 'bonus' => 10],
            5 => ['name' => 'System-Wide Exchange', 'cost' => 12000, 'bonus' => 10],
            6 => ['name' => 'Automated Freight Network', 'cost' => 30000, 'bonus' => 15],
            7 => ['name' => 'Helium-3 Extraction Syndicate', 'cost' => 65000, 'bonus' => 15],
            8 => ['name' => 'Deep Space Commerce Hub', 'cost' => 100000, 'bonus' => 15],
            9 => ['name' => 'Galactic Credit Reserve', 'cost' => 150000, 'bonus' => 15],
            10 => ['name' => 'Quantum Logistics Grid', 'cost' => 250000, 'bonus' => 20],
            11 => ['name' => 'Dyson Swarm Assembly', 'cost' => 400000, 'bonus' => 20],
            12 => ['name' => 'Interstellar Banking Clan', 'cost' => 700000, 'bonus' => 20],
            13 => ['name' => 'Antimatter Wealth Vault', 'cost' => 1200000, 'bonus' => 20],
            14 => ['name' => 'Dark Matter Refinery', 'cost' => 2000000, 'bonus' => 25],
            15 => ['name' => 'Wormhole Toll Authority', 'cost' => 3500000, 'bonus' => 25],
            16 => ['name' => 'Cosmic Megacorporation', 'cost' => 6000000, 'bonus' => 25],
            17 => ['name' => 'Stellar Engine Mint', 'cost' => 10000000, 'bonus' => 25],
            18 => ['name' => 'Trans-Dimensional Market', 'cost' => 15000000, 'bonus' => 25],
            19 => ['name' => 'Multiversal Trade Nexus', 'cost' => 20000000, 'bonus' => 25],
            20 => ['name' => 'Omega Point Economy', 'cost' => 25000000, 'bonus' => 25],
        ];

        $economyData = [];
        foreach ($eLevels as $lvl => $d) {
            $economyData[] = [
                'structure_id' => $economy['id'],
                'level' => $lvl,
                'cost' => $d['cost'],
                'buff_economy' => $d['bonus'],
                'buff_name' => $d['name'],
                'player_level_req' => floor($lvl / 1.5) + 1
            ];
        }
        $this->table('structure_levels')->insert($economyData)->saveData();

        // 3. Armory Levels
        $armory = $this->fetchRow("SELECT id FROM structures WHERE slug = 'armory'");
        $aLevels = [];
        for ($i = 1; $i <= 20; $i++) {
            $aLevels[] = [
                'structure_id' => $armory['id'],
                'level' => $i,
                'cost' => floor(100000 * pow(2.1, $i - 1)),
                'buff_name' => "Tech Rank {$i}",
                'player_level_req' => $i
            ];
        }
        $this->table('structure_levels')->insert($aLevels)->saveData();
    }
}