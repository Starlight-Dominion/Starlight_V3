<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class GameConfigSeeder extends AbstractSeed
{
    public function run(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0');
        $this->table('units')->truncate();
        $this->table('foundation_tiers')->truncate();
        $this->table('stable_levels')->truncate();
        $this->table('armory_upgrades')->truncate();
        
        $this->table('structures')->truncate();
        $this->table('structure_levels')->truncate();
        $this->table('structure_upgrade_options')->truncate();
        $this->table('kingdom_structure_upgrades')->truncate();
        $this->execute('SET FOREIGN_KEY_CHECKS=1');

        // --- 1. Seed Units (Keep existing logic) ---
        $unitsConfig = require __DIR__ . '/../../config/units.php';
        $unitsToSeed = [];
        foreach ($unitsConfig as $slug => $data) {
            $unitsToSeed[] = [
                'slug' => $slug,
                'name' => $data['name'],
                'description' => $data['description'],
                'cost_gold' => $data['cost_gold'],
                'cost_citizens' => $data['cost_citizens'],
                'cost_turns' => $data['cost_turns'],
                'power_offense' => $data['power_offense'],
                'power_defense' => $data['power_defense'],
                'requirement_slug' => null,
                'foundation_level_req' => 0,
                'stable_level_req' => 0,
                'armory_level_req' => 0
            ];
        }
        $this->table('units')->insert($unitsToSeed)->saveData();

        // --- 2. Seed Core Structures ---
        
        // A. Foundation
        $fConfig = require __DIR__ . '/../../config/foundation.php';
        $fId = $this->table('structures')->insert([
            'slug' => 'foundation',
            'name' => 'Kingdom Foundation',
            'description' => 'The bedrock of your realm.',
            'upgrade_slots' => 1,
            'max_level' => 10
        ])->saveData();
        
        $fRow = $this->fetchRow("SELECT id FROM structures WHERE slug = 'foundation'");
        $fId = $fRow['id'];

        $fLevels = [];
        foreach ($fConfig['tiers'] as $lvl => $d) {
            $fLevels[] = [
                'structure_id' => $fId,
                'level' => $lvl,
                'cost' => $d['cost'],
                'buff_name' => $d['name'],
                'buff_hp' => $d['hp'],
                'player_level_req' => $d['player_level_req']
            ];
        }
        $this->table('structure_levels')->insert($fLevels)->saveData();

        foreach ($fConfig['upgrades'] as $slug => $d) {
            $this->table('structure_upgrade_options')->insert([
                'structure_id' => $fId,
                'slug' => $slug,
                'name' => $d['name'],
                'description' => $d['description'],
                'cost_multiplier' => $d['cost_multiplier'],
                'bonus_type' => $d['bonus_type'],
                'bonus_value' => $d['bonus_value']
            ])->saveData();
        }

        // B. Royal Armory
        $aConfig = require __DIR__ . '/../../config/armory.php';
        $this->table('structures')->insert([
            'slug' => 'armory',
            'name' => 'Royal Armory',
            'description' => 'Forges advanced weaponry.',
            'upgrade_slots' => 1,
            'max_level' => 10,
            'dependency_slug' => 'foundation'
        ])->saveData();

        $aRow = $this->fetchRow("SELECT id FROM structures WHERE slug = 'armory'");
        $aId = $aRow['id'];

        $aLevels = [];
        foreach ($aConfig['upgrade_costs'] as $lvl => $cost) {
            $aLevels[] = [
                'structure_id' => $aId,
                'level' => $lvl,
                'cost' => $cost
            ];
        }
        $this->table('structure_levels')->insert($aLevels)->saveData();

        // C. Royal Stable
        $sConfig = require __DIR__ . '/../../config/stable.php';
        $this->table('structures')->insert([
            'slug' => 'stable',
            'name' => 'Royal Stable',
            'description' => 'Housings for your active units.',
            'upgrade_slots' => 1,
            'max_level' => 30,
            'dependency_slug' => 'foundation',
            'dependency_multiplier' => 3
        ])->saveData();

        $sRow = $this->fetchRow("SELECT id FROM structures WHERE slug = 'stable'");
        $sId = $sRow['id'];

        $sLevels = [];
        foreach ($sConfig['levels'] as $lvl => $d) {
            $sLevels[] = [
                'structure_id' => $sId,
                'level' => $lvl,
                'cost' => $d['cost'],
                'capacity' => $d['capacity']
            ];
        }
        $this->table('structure_levels')->insert($sLevels)->saveData();
    }
}
