<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ExpandStructureVariables extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('structure_levels');
        
        // Add new buff columns if they don't exist
        if (!$table->hasColumn('buff_citizens_per_tick')) {
            $table->addColumn('buff_citizens_per_tick', 'integer', ['default' => 0])
                  ->addColumn('buff_unit_guards', 'integer', ['default' => 0])
                  ->addColumn('buff_unit_soldiers', 'integer', ['default' => 0])
                  ->addColumn('buff_unit_spies', 'integer', ['default' => 0])
                  ->addColumn('buff_unit_sentries', 'integer', ['default' => 0])
                  ->update();
        }

        // 1. Seed New Structures
        $structures = [
            ['id' => 4, 'slug' => 'housing', 'name' => 'Civilian Housing', 'description' => 'Supports population growth and provides a baseline for mobilization.', 'max_level' => 5],
            ['id' => 5, 'slug' => 'mercenary_market', 'name' => 'Mercenary Market', 'description' => 'Recruit specialized reinforcements to bolster your frontline.', 'max_level' => 5]
        ];

        foreach ($structures as $s) {
            $this->execute("INSERT IGNORE INTO structures (id, slug, name, description, max_level) VALUES ({$s['id']}, '{$s['slug']}', '{$s['name']}', '{$s['description']}', {$s['max_level']})");
        }

        // 2. Seed Housing Levels
        $housingLevels = [
            1 => ['cost' => 0, 'cit' => 50],
            2 => ['cost' => 1000, 'cit' => 60],
            3 => ['cost' => 5000, 'cit' => 75],
            4 => ['cost' => 20000, 'cit' => 100],
            5 => ['cost' => 75000, 'cit' => 150],
        ];

        foreach ($housingLevels as $lvl => $data) {
            $this->execute("INSERT IGNORE INTO structure_levels (structure_id, level, cost, buff_citizens_per_tick, buff_name) 
                            VALUES (4, {$lvl}, {$data['cost']}, {$data['cit']}, 'Tier {$lvl}')");
        }

        // 3. Seed Mercenary Market Levels
        $mercLevels = [
            1 => ['cost' => 5000, 'g' => 4, 's' => 2, 'sp' => 1, 'se' => 1],
            2 => ['cost' => 15000, 'g' => 8, 's' => 4, 'sp' => 2, 'se' => 2],
            3 => ['cost' => 40000, 'g' => 16, 's' => 8, 'sp' => 4, 'se' => 4],
            4 => ['cost' => 100000, 'g' => 32, 's' => 16, 'sp' => 8, 'se' => 8],
            5 => ['cost' => 250000, 'g' => 64, 's' => 32, 'sp' => 16, 'se' => 16],
        ];

        foreach ($mercLevels as $lvl => $data) {
            $this->execute("INSERT IGNORE INTO structure_levels (structure_id, level, cost, buff_unit_guards, buff_unit_soldiers, buff_unit_spies, buff_unit_sentries, buff_name) 
                            VALUES (5, {$lvl}, {$data['cost']}, {$data['g']}, {$data['s']}, {$data['sp']}, {$data['se']}, 'Rank {$lvl}')");
        }
    }

    public function down(): void
    {
        $this->execute("DELETE FROM structure_levels WHERE structure_id IN (4, 5)");
        $this->execute("DELETE FROM structures WHERE id IN (4, 5)");
        
        $table = $this->table('structure_levels');
        $table->removeColumn('buff_citizens_per_tick')
              ->removeColumn('buff_unit_guards')
              ->removeColumn('buff_unit_soldiers')
              ->removeColumn('buff_unit_spies')
              ->removeColumn('buff_unit_sentries')
              ->update();
    }
}
