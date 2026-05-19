<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class OverhaulStructuresDynamicSystem extends AbstractMigration
{
    public function change(): void
    {
        // 1. Structures Table (The Building types)
        $this->table('structures')
            ->addColumn('slug', 'string', ['limit' => 32])
            ->addColumn('name', 'string', ['limit' => 64])
            ->addColumn('description', 'text')
            ->addColumn('upgrade_slots', 'integer', ['default' => 1])
            ->addColumn('max_level', 'integer', ['default' => 10])
            ->addColumn('dependency_slug', 'string', ['limit' => 32, 'null' => true]) // Usually 'foundation'
            ->addColumn('dependency_multiplier', 'integer', ['default' => 1]) // e.g. stable is 3x foundation
            ->addIndex(['slug'], ['unique' => true])
            ->create();

        // 2. Structure Levels Table (Stats per level)
        $this->table('structure_levels', ['id' => false, 'primary_key' => ['structure_id', 'level']])
            ->addColumn('structure_id', 'integer', ['signed' => false])
            ->addColumn('level', 'integer')
            ->addColumn('cost', 'biginteger')
            
            // Buffs
            ->addColumn('buff_name', 'string', ['limit' => 64, 'null' => true]) // e.g. "Wood", "Reinforced"
            ->addColumn('buff_hp', 'biginteger', ['default' => 0])
            ->addColumn('buff_offense', 'integer', ['default' => 0])
            ->addColumn('buff_defense', 'integer', ['default' => 0])
            ->addColumn('buff_spy_offense', 'integer', ['default' => 0])
            ->addColumn('buff_spy_defense', 'integer', ['default' => 0])
            ->addColumn('buff_economy', 'integer', ['default' => 0])
            ->addColumn('buff_charisma', 'integer', ['default' => 0])
            
            // Requirements
            ->addColumn('player_level_req', 'integer', ['default' => 0])
            ->addColumn('capacity', 'integer', ['null' => true]) // specifically for stable/housing
            
            ->addForeignKey('structure_id', 'structures', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
            ->create();

        // 3. Structure Upgrade Options (Available mods per building)
        $this->table('structure_upgrade_options')
            ->addColumn('structure_id', 'integer', ['signed' => false])
            ->addColumn('slug', 'string', ['limit' => 32])
            ->addColumn('name', 'string', ['limit' => 64])
            ->addColumn('description', 'text')
            ->addColumn('cost_multiplier', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0.5])
            
            // The bonus it provides
            ->addColumn('bonus_type', 'string', ['limit' => 32]) // hp_percentage, defense_percentage, etc
            ->addColumn('bonus_value', 'decimal', ['precision' => 10, 'scale' => 2])
            
            ->addForeignKey('structure_id', 'structures', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
            ->addIndex(['structure_id', 'slug'], ['unique' => true])
            ->create();

        // 4. Kingdom Structure Upgrades (What a player has installed)
        $this->table('kingdom_structure_upgrades', ['id' => false, 'primary_key' => ['kingdom_id', 'structure_id', 'slot']])
            ->addColumn('kingdom_id', 'integer', ['signed' => false])
            ->addColumn('structure_id', 'integer', ['signed' => false])
            ->addColumn('slot', 'integer', ['default' => 1])
            ->addColumn('upgrade_option_id', 'integer', ['signed' => false])
            
            ->addForeignKey('kingdom_id', 'kingdoms', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
            ->addForeignKey('structure_id', 'structures', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
            ->addForeignKey('upgrade_option_id', 'structure_upgrade_options', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
            ->create();
    }
}
